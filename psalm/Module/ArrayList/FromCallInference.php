<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class FromCallInference implements AfterExpressionAnalysisInterface
{
    private const FROM = 'Fp4\PHP\ArrayList\from';
    private const SINGLETON = 'Fp4\PHP\ArrayList\singleton';
    private const FROM_NON_EMPTY = 'Fp4\PHP\ArrayList\fromNonEmpty';
    private const FROM_ITERABLE = 'Fp4\PHP\ArrayList\fromIterable';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            Widening::widen($event, [self::FROM, self::SINGLETON, self::FROM_NON_EMPTY, self::FROM_ITERABLE]),
            constNull(...),
        );
    }
}
