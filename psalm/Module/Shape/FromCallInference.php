<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Shape;

use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\AsNonLiteralTypeConfig;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Narrowing;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class FromCallInference implements AfterExpressionAnalysisInterface
{
    private const FROM = 'Fp4\PHP\Shape\from';
    private const FROM_LITERAL = 'Fp4\PHP\Shape\fromLiteral';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            O\first(
                fn() => Widening::widen($event, [self::FROM], new AsNonLiteralTypeConfig(
                    preserveKeyedArrayShape: true,
                )),
                fn() => Narrowing::assertNarrowed($event, [self::FROM_LITERAL]),
            ),
            constNull(...),
        );
    }
}
