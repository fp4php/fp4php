<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Closure;
use Fp4\PHP\ArrayDictionary as D;
use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Expr;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\StatementsSource;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function count;
use function Fp4\PHP\Combinator\pipe;

/**
 * @psalm-type Score
 *     = FunctionReturnTypeProviderEvent
 *     | MethodReturnTypeProviderEvent
 *     | AfterExpressionAnalysisEvent
 *     | StatementsSource
 */
final class Types
{
    /**
     * @param Score $toScope
     * @return Closure(Union|Atomic): void
     */
    public function set(Expr $expr, object $toScope): Closure
    {
        return function(Union|Atomic $type) use ($expr, $toScope): void {
            self::getNodeTypeProvider($toScope)->setType(
                node: $expr,
                type: $type instanceof Atomic ? new Union([$type]) : $type,
            );
        };
    }

    /**
     * @param Score $fromScope
     * @return Closure(Expr): O\Option<Union>
     */
    public function get(object $fromScope): Closure
    {
        return fn(Expr $expr) => pipe(
            $expr,
            self::getNodeTypeProvider($fromScope)->getType(...),
            O\fromNullable(...),
        );
    }

    /**
     * @no-named-arguments
     */
    public function combine(Union $type1, Union $type2, Union ...$rest): Union
    {
        return TypeCombiner::combine(
            types: pipe(
                [$type1, $type2, ...$rest],
                L\flatMap(fn(Union $type) => pipe(
                    $type->getAtomicTypes(),
                    D\values(...),
                )),
            ),
            codebase: PsalmApi::$codebase,
        );
    }

    /**
     * @return Closure(Union $from): O\Option<Union>
     */
    public static function remove(Union $types): Closure
    {
        return function(Union $from) use ($types) {
            if ($from->getId() === $types->getId()) {
                return O\none;
            }

            if (1 === count($from->getAtomicTypes())) {
                return O\none;
            }

            $builder = $from->getBuilder();

            foreach ($types->getAtomicTypes() as $atomic) {
                $builder->removeType($atomic->getKey());
            }

            return O\some($builder->freeze());
        };
    }

    /**
     * @param Score $scope
     */
    private static function getNodeTypeProvider(object $scope): NodeTypeProvider
    {
        return match (true) {
            $scope instanceof StatementsSource => $scope
                ->getNodeTypeProvider(),
            $scope instanceof AfterExpressionAnalysisEvent || $scope instanceof FunctionReturnTypeProviderEvent => $scope
                ->getStatementsSource()
                ->getNodeTypeProvider(),
            $scope instanceof MethodReturnTypeProviderEvent => $scope
                ->getSource()
                ->getNodeTypeProvider(),
        };
    }
}
