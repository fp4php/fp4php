<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement;

use Closure;
use Fp4\PHP\Evidence as Ev;
use Fp4\PHP\Option as O;
use Fp4\PHP\Option\Option;
use Fp4\PHP\PsalmIntegration\PsalmUtils\FunctionType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\pipe;

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
        FunctionType $type,
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
                type: $type,
                typeParams: $i->typeParams,
                predicate: $i->predicate,
                source: $i->source,
                context: $event->getContext(),
            )),
            O\map(fn(Refinement $refinement) => $refinement->refine()),
            O\flatMap(fn(RefineTypeParams $refined) => pipe(
                O\some($event->getExpr()),
                O\flatMap(PsalmApi::$type->get($event)),
                O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TClosure::class)),
                O\map(fn(TClosure $closure) => $closure->replace(
                    params: $closure->params,
                    return_type: $toReturnType($refined, $closure->return_type ?? Type::getMixed()),
                )),
                O\map(PsalmApi::$create->union(...)),
            )),
            O\tap(PsalmApi::$type->set($event->getExpr(), $event)),
        );
    }
}
