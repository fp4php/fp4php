<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayDictionary;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\FilterRefinement;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefineTypeParams;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class FilterCallRefinement implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            self::refineFilter('Fp4\PHP\Module\ArrayDictionary\filter')($event),
            O\orElse(fn() => self::refineFilter('Fp4\PHP\Module\ArrayDictionary\filterKV')($event)),
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
            getKeyType: O\some(fn(Union $inferred) => pipe(
                O\some($inferred),
                O\flatMap(PsalmApi::$types->asSingleAtomicOf(TArray::class)),
                O\map(fn(TArray $keyed) => $keyed->type_params[1]),
            )),
            getValType: fn(Union $inferred) => pipe(
                O\some($inferred),
                O\flatMap(PsalmApi::$types->asSingleAtomicOf(TArray::class)),
                O\map(fn(TArray $keyed) => $keyed->type_params[1]),
            ),
            toReturnType: fn(RefineTypeParams $refined) => new Union([
                new TArray([$refined->key, $refined->value]),
            ]),
        );
    }
}
