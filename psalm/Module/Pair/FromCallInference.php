<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Pair;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\AsNonLiteralTypeConfig;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class FromCallInference implements AfterExpressionAnalysisInterface
{
    private const FROM = 'Fp4\PHP\Pair\from';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            Widening::widen($event, [self::FROM], new AsNonLiteralTypeConfig(
                preserveKeyedArrayShape: true,
            )),
            constNull(...),
        );
    }
}
