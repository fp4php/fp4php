<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\FilterRefinement;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefinementType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefineTypeParams;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class FilterCallRefinement implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            O\first(
                fn() => pipe($event, self::refineFilter('Fp4\PHP\Module\ArrayList\filter')),
                fn() => pipe($event, self::refineFilter('Fp4\PHP\Module\ArrayList\filterKV')),
            ),
            constNull(...),
        );
    }

    /**
     * @param non-empty-string $function
     * @return Closure(AfterExpressionAnalysisEvent): Option<mixed>
     */
    private static function refineFilter(string $function): Closure
    {
        return FilterRefinement::refine(
            function: $function,
            getKeyType: O\none,
            getValType: fn(Union $inferred) => pipe(
                O\some($inferred),
                O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TKeyedArray::class)),
                O\filter(fn(TKeyedArray $keyed) => $keyed->isGenericList()),
                O\map(fn(TKeyedArray $keyed) => $keyed->getGenericValueType()),
            ),
            toReturnType: fn(RefineTypeParams $refined) => PsalmApi::$create->list($refined->value),
            type: RefinementType::Value,
        );
    }
}
