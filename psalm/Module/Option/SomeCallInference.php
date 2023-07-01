<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Option;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Narrowing;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class SomeCallInference implements AfterExpressionAnalysisInterface
{
    private const SOME = 'Fp4\PHP\Module\Option\some';
    private const WHEN = 'Fp4\PHP\Module\Option\when';
    private const TRY_CATCH = 'Fp4\PHP\Module\Option\tryCatch';
    private const FROM_NULLABLE = 'Fp4\PHP\Module\Option\fromNullable';
    private const FROM_LITERAL = 'Fp4\PHP\Module\Option\fromLiteral';
    private const FROM_NULLABLE_LITERAL = 'Fp4\PHP\Module\Option\fromNullableLiteral';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            O\first(
                fn() => Widening::widen($event, [self::SOME, self::FROM_NULLABLE, self::WHEN, self::TRY_CATCH]),
                fn() => Narrowing::assertNarrowed($event, [self::FROM_LITERAL, self::FROM_NULLABLE_LITERAL]),
            ),
            constNull(...),
        );
    }
}
