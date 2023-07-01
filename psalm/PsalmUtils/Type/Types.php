<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\StatementsSource;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;

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
     * @return Closure(Expr): Option<Union>
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
