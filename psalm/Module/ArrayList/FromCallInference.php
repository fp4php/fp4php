<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class FromCallInference implements AfterExpressionAnalysisInterface
{
    private const FROM = 'Fp4\PHP\Module\ArrayList\from';
    private const SINGLETON = 'Fp4\PHP\Module\ArrayList\singleton';
    private const FROM_NON_EMPTY = 'Fp4\PHP\Module\ArrayList\fromNonEmpty';
    private const FROM_ITERABLE = 'Fp4\PHP\Module\ArrayList\fromIterable';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            Widening::widen($event, [self::FROM, self::SINGLETON, self::FROM_NON_EMPTY, self::FROM_ITERABLE]),
            constNull(...),
        );
    }
}
