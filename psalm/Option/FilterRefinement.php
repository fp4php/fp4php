<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Option;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\PredicateExtractor;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\Refinement;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefinementType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefineTypeParams;
use Fp4\PHP\Type\Option;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class FilterRefinement implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            O\bindable(),
            O\bind(
                source: fn() => pipe($event->getStatementsSource(), Ev\proveOf(StatementsAnalyzer::class)),
                predicate: fn() => pipe($event, PredicateExtractor::extract('Fp4\PHP\Module\Option\filter')),
                typeParams: fn() => pipe($event, RefineTypeParams::from(
                    extractKey: fn() => O\some(Type::getNever()),
                    extractValue: fn(Union $closureReturnType) => pipe(
                        $closureReturnType,
                        PsalmApi::$types->asSingleGenericObjectOf(Option::class),
                        O\flatMap(fn(TGenericObject $option) => L\first($option->type_params)),
                    ),
                )),
            ),
            O\map(fn($i) => new Refinement(
                type: RefinementType::Value,
                typeParams: $i->typeParams,
                predicate: $i->predicate,
                source: $i->source,
                context: $event->getContext(),
            )),
            O\map(fn(Refinement $refinement) => $refinement->refine()),
            O\flatMap(self::createReturnType($event)),
            O\tap(PsalmApi::$types->setExprType($event->getExpr(), $event)),
            constNull(...),
        );
    }

    /**
     * @return Closure(RefineTypeParams): Option<Union>
     */
    private static function createReturnType(AfterExpressionAnalysisEvent $event): Closure
    {
        return fn(RefineTypeParams $typeParams) => pipe(
            $event->getExpr(),
            PsalmApi::$types->getExprType($event),
            O\flatMap(PsalmApi::$types->asSingleAtomicOf(TClosure::class)),
            O\map(fn(TClosure $closure) => $closure->replace(
                params: $closure->params,
                return_type: new Union([
                    new TGenericObject(Option::class, [$typeParams->value]),
                ]),
            )),
            O\map(PsalmApi::$types->asUnion(...)),
        );
    }
}
