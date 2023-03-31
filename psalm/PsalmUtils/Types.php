<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils;

use Closure;
use PhpParser\Node\Expr;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Type\Union;

final class Types
{
    /**
     * @return Closure(Expr): void
     */
    public function setExprType(Union $type, AfterExpressionAnalysisEvent|StatementsSource $scope): Closure
    {
        return function (Expr $expr) use ($type, $scope): void {
            $types = match (true) {
                $scope instanceof StatementsSource => $scope->getNodeTypeProvider(),
                $scope instanceof AfterExpressionAnalysisEvent => $scope->getStatementsSource()->getNodeTypeProvider(),
            };

            $types->setType($expr, $type);
        };
    }
}
