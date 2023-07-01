<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement;

use Closure;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;

final class FilterRefinement
{
    /**
     * @param non-empty-string $function
     * @param Option<callable(Union): Option<Union>> $getKeyType
     * @param callable(Union): Option<Union> $getValType
     * @param callable(RefineTypeParams, Union): Union $toReturnType
     * @return Closure(AfterExpressionAnalysisEvent): Option
     */
    public static function refine(
        string $function,
        Option $getKeyType,
        callable $getValType,
        callable $toReturnType,
    ): Closure {
        return fn(AfterExpressionAnalysisEvent $event) => pipe(
            O\bindable(),
            O\bind(
                source: fn() => pipe($event->getStatementsSource(), Ev\proveOf(StatementsAnalyzer::class)),
                predicate: fn() => pipe($event, PredicateExtractor::extract($function)),
                typeParams: fn() => O\isNone($getKeyType)
                    ? pipe($event, RefineTypeParams::from(fn() => O\some(Type::getNever()), $getValType))
                    : pipe($event, RefineTypeParams::from($getKeyType->value, $getValType)),
            ),
            O\map(fn($i) => new Refinement(
                type: O\isNone($getKeyType) ? RefinementType::Value : RefinementType::KeyValue,
                typeParams: $i->typeParams,
                predicate: $i->predicate,
                source: $i->source,
                context: $event->getContext(),
            )),
            O\map(fn(Refinement $refinement) => $refinement->refine()),
            O\flatMap(fn(RefineTypeParams $refined) => pipe(
                O\some($event->getExpr()),
                O\flatMap(PsalmApi::$types->getExprType($event)),
                O\flatMap(PsalmApi::$types->asSingleAtomicOf(TClosure::class)),
                O\map(fn(TClosure $closure) => $closure->replace(
                    params: $closure->params,
                    return_type: $toReturnType($refined, $closure->return_type ?? Type::getMixed()),
                )),
                O\map(PsalmApi::$create->union(...)),
            )),
            O\tap(PsalmApi::$types->setExprType($event->getExpr(), $event)),
        );
    }
}
