<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Closure;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\StatementsSource;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;
use function in_array;
use function is_array;

final class Types
{
    /**
     * @return Closure(Union|Atomic): void
     */
    public function setExprType(Expr $expr, MethodReturnTypeProviderEvent|AfterExpressionAnalysisEvent|StatementsSource $scope): Closure
    {
        return function(Union|Atomic $type) use ($expr, $scope): void {
            self::getNodeTypeProvider($scope)->setType(
                node: $expr,
                type: $type instanceof Atomic ? new Union([$type]) : $type,
            );
        };
    }

    /**
     * @return Closure(Expr): Option<Union>
     */
    public function getExprType(MethodReturnTypeProviderEvent|AfterExpressionAnalysisEvent|StatementsSource $scope): Closure
    {
        return fn(Expr $expr) => pipe(
            $expr,
            self::getNodeTypeProvider($scope)->getType(...),
            O\fromNullable(...),
        );
    }

    public function asNonLiteralType(Union $type): Union
    {
        return AsNonLiteralType::transform($type);
    }

    /**
     * @return Option<Atomic>
     */
    public function asSingleAtomic(Union $type): Option
    {
        return pipe(
            O\some($type),
            O\filter(fn(Union $t) => $t->isSingle()),
            O\map(fn(Union $t) => $t->getSingleAtomic()),
        );
    }

    /**
     * @template TAtomic of Atomic
     *
     * @param class-string<TAtomic>|non-empty-list<class-string<TAtomic>> $class
     * @return Closure(Union): Option<TAtomic>
     */
    public function asSingleAtomicOf(string|array $class): Closure
    {
        return fn(Union $type) => pipe(
            $type,
            PsalmApi::$types->asSingleAtomic(...),
            O\flatMap(Ev\proveOf($class)),
        );
    }

    /**
     * @param class-string|non-empty-list<class-string> $class
     * @return Closure(Union): Option<Atomic\TGenericObject>
     */
    public function asSingleGenericObjectOf(string|array $class): Closure
    {
        return fn(Union $type) => pipe(
            $type,
            PsalmApi::$types->asSingleAtomic(...),
            O\flatMap(Ev\proveOf(Atomic\TGenericObject::class)),
            O\filter(fn(Atomic\TGenericObject $object) => in_array($object->value, is_array($class) ? $class : [$class], true)),
        );
    }

    public function asUnion(Atomic $atomic): Union
    {
        return new Union([$atomic]);
    }

    private static function getNodeTypeProvider(MethodReturnTypeProviderEvent|AfterExpressionAnalysisEvent|StatementsSource $scope): NodeTypeProvider
    {
        return match (true) {
            $scope instanceof StatementsSource => $scope->getNodeTypeProvider(),
            $scope instanceof AfterExpressionAnalysisEvent => $scope->getStatementsSource()->getNodeTypeProvider(),
            $scope instanceof MethodReturnTypeProviderEvent => $scope->getSource()->getNodeTypeProvider(),
        };
    }
}
