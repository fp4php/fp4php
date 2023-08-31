<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Either;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class LeftRightCallInference implements AfterExpressionAnalysisInterface
{
    private const LEFT = 'Fp4\PHP\Either\left';
    private const RIGHT = 'Fp4\PHP\Either\right';
    private const TRY_CATCH = 'Fp4\PHP\Either\tryCatch';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            Widening::widen($event, [self::LEFT, self::RIGHT, self::TRY_CATCH]),
            constNull(...),
        );
    }
}
