<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayDictionary;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Module\Combinator\constNull;
use function Fp4\PHP\Module\Combinator\pipe;

final class FromCallInference implements AfterExpressionAnalysisInterface
{
    private const FROM = 'Fp4\PHP\Module\ArrayDictionary\from';
    private const FROM_NON_EMPTY = 'Fp4\PHP\Module\ArrayDictionary\fromNonEmpty';
    private const FROM_ITERABLE = 'Fp4\PHP\Module\ArrayDictionary\fromIterable';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            Widening::widen($event, [self::FROM, self::FROM_NON_EMPTY, self::FROM_ITERABLE]),
            constNull(...),
        );
    }
}
