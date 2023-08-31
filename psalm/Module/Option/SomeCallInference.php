<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Option;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class SomeCallInference implements AfterExpressionAnalysisInterface
{
    private const SOME = 'Fp4\PHP\Option\some';
    private const WHEN = 'Fp4\PHP\Option\when';
    private const TRY_CATCH = 'Fp4\PHP\Option\tryCatch';
    private const FROM_NULLABLE = 'Fp4\PHP\Option\fromNullable';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            Widening::widen($event, [self::SOME, self::FROM_NULLABLE, self::WHEN, self::TRY_CATCH]),
            constNull(...),
        );
    }
}
