<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Option;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ConstFetch;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class NoneConstWidening implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            O\some($event->getExpr()),
            O\filter(self::isNoneFetch(...)),
            O\map(fn() => self::optionNever()),
            O\tap(PsalmApi::$types->setExprType($event->getExpr(), $event)),
            constNull(...),
        );
    }

    private static function optionNever(): Union
    {
        return new Union([
            new TGenericObject(Option::class, [
                new Union([
                    new TNever(),
                ]),
            ]),
        ]);
    }

    private static function isNoneFetch(Expr $expr): bool
    {
        return $expr instanceof ConstFetch && 'Fp4\PHP\Module\Option\none' === $expr->name->getAttribute('resolvedName');
    }
}
