<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr;
use Psalm\NodeTypeProvider;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;

final class Types
{
    /**
     * @return Closure(Expr): void
     */
    public function setExprType(Union|Atomic $type, AfterExpressionAnalysisEvent|StatementsSource $scope): Closure
    {
        return function (Expr $expr) use ($type, $scope): void {
            self::getNodeTypeProvider($scope)->setType(
                node: $expr,
                type: $type instanceof Atomic ? new Union([$type]) : $type,
            );
        };
    }

    /**
     * @return Closure(Union|Atomic): void
     */
    public function setType(Expr $expr, AfterExpressionAnalysisEvent|StatementsSource $scope): Closure
    {
        return function (Union|Atomic $type) use ($expr, $scope): void {
            self::getNodeTypeProvider($scope)->setType(
                node: $expr,
                type: $type instanceof Atomic ? new Union([$type]) : $type,
            );
        };
    }

    /**
     * @return Closure(Expr): Option<Union>
     */
    public function getExprType(AfterExpressionAnalysisEvent|StatementsSource $scope): Closure
    {
        return fn (Expr $expr) => pipe(
            self::getNodeTypeProvider($scope)->getType($expr),
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
            O\filter(fn (Union $t) => $t->isSingle()),
            O\map(fn (Union $t) => $t->getSingleAtomic()),
        );
    }

    public function asUnion(Atomic $atomic): Union
    {
        return new Union([$atomic]);
    }

    private static function getNodeTypeProvider(AfterExpressionAnalysisEvent|StatementsSource $scope): NodeTypeProvider
    {
        return match (true) {
            $scope instanceof StatementsSource => $scope->getNodeTypeProvider(),
            $scope instanceof AfterExpressionAnalysisEvent => $scope->getStatementsSource()->getNodeTypeProvider(),
        };
    }
}
