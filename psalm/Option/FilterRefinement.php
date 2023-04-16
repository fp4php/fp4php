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
use Psalm\Storage\FunctionLikeParameter;
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
                predicate: fn() => PredicateExtractor::extract($event, 'Fp4\PHP\Module\Option\filter'),
                typeParams: fn() => self::getOptionTypeParam($event),
                source: fn() => pipe($event->getStatementsSource(), Ev\proveOf(StatementsAnalyzer::class)),
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
            O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
            O\flatMap(Ev\proveOf(TClosure::class)),
            O\map(fn(TClosure $closure) => $closure->replace(
                params: $closure->params,
                return_type: new Union([
                    new TGenericObject(Option::class, [$typeParams->value]),
                ]),
            )),
            O\map(PsalmApi::$types->asUnion(...)),
        );
    }

    /**
     * @return Option<RefineTypeParams>
     */
    private static function getOptionTypeParam(AfterExpressionAnalysisEvent $event): Option
    {
        return pipe(
            $event->getExpr(),
            PsalmApi::$types->getExprType($event),
            O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
            O\flatMap(Ev\proveOf(TClosure::class)),
            O\flatMap(fn(TClosure $closure) => O\fromNullable($closure->params)),
            O\flatMap(fn(array $params) => L\first($params)),
            O\flatMap(fn(FunctionLikeParameter $param) => O\fromNullable($param->type)),
            O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
            O\flatMap(Ev\proveOf(TGenericObject::class)),
            O\filter(fn(TGenericObject $option) => Option::class === $option->value),
            O\flatMap(fn(TGenericObject $option) => L\first($option->type_params)),
            O\map(fn(Union $type) => new RefineTypeParams(
                key: Type::getNever(),
                value: $type,
            )),
        );
    }
}
