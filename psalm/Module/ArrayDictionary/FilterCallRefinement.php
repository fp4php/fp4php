<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayDictionary;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\FunctionType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\FilterRefinement;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefineTypeParams;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Combinator\constNull;
use function Fp4\PHP\Module\Combinator\pipe;

final class FilterCallRefinement implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            O\first(
                fn() => pipe($event, self::refineFilter('Fp4\PHP\Module\ArrayDictionary\filter')),
                fn() => pipe($event, self::refineFilter('Fp4\PHP\Module\ArrayDictionary\filterKV')),
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
            getKeyType: O\some(fn(Union $inferred) => pipe(
                O\some($inferred),
                O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TArray::class)),
                O\flatMap(fn(TArray $keyed) => pipe(
                    $keyed->type_params,
                    L\first(...),
                )),
            )),
            getValType: fn(Union $inferred) => pipe(
                O\some($inferred),
                O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TArray::class)),
                O\flatMap(fn(TArray $keyed) => pipe(
                    $keyed->type_params,
                    L\second(...),
                )),
            ),
            toReturnType: fn(RefineTypeParams $refined) => PsalmApi::$create->array($refined->key, $refined->value),
            type: str_ends_with($function, 'filterKV') ? FunctionType::KeyValue : FunctionType::Value,
        );
    }
}
