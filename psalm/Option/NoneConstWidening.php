<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Option;

use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Union;

final class NoneConstWidening implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();

        if (self::isNoneFetch($expr)) {
            $source = $event->getStatementsSource();
            $typeProvider = $source->getNodeTypeProvider();

            $typeProvider->setType($expr, self::asOptionNever());
        }

        return null;
    }

    private static function asOptionNever(): Union
    {
        return new Union([
            new TGenericObject(Option::class, [
                new Union([
                    new TNever(),
                ])
            ])
        ]);
    }

    private static function isNoneFetch(Expr $expr): bool
    {
        return $expr instanceof ConstFetch && $expr->name->getAttribute('resolvedName') === 'Fp4\PHP\Module\Option\none';
    }
}
