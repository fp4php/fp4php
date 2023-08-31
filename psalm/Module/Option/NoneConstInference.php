<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Option;

use Fp4\PHP\Option as O;
use Fp4\PHP\Option\Option;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class NoneConstInference implements AfterExpressionAnalysisInterface
{
    private const NONE = 'Fp4\PHP\Option\none';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            Widening::widen($event, [self::NONE]),
            O\map(fn() => pipe(
                PsalmApi::$create->never(),
                PsalmApi::$create->genericObject(Option::class),
            )),
            O\tap(PsalmApi::$type->set($event->getExpr(), $event)),
            constNull(...),
        );
    }
}
