<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Property;

use Closure;
use Fp4\PHP\ArrayDictionary as D;
use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Evidence as Ev;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Provider\ReturnTypeProvider\GetObjectVarsReturnTypeProvider;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\constVoid;
use function Fp4\PHP\Combinator\pipe;

final class PropertyInferenceHandler
{
    /**
     * @template TKind of Atomic
     *
     * @param Closure(Union): O\Option<TKind> $getKind
     * @param Closure(TKind): O\Option<Union> $getKindParam
     * @param Closure(TKind, Union): Union $mapKindParam
     * @return Closure(AfterExpressionAnalysisEvent): O\Option<void>
     */
    public static function handle(
        string $function,
        Closure $getKind,
        Closure $getKindParam,
        Closure $mapKindParam,
    ): Closure {
        return fn(AfterExpressionAnalysisEvent $event) => pipe(
            O\some($event->getExpr()),
            O\filterOf(FuncCall::class),
            O\filter(fn(FuncCall $call) => $function === $call->name->getAttribute('resolvedName')),
            O\filter(fn(FuncCall $call) => !$call->isFirstClassCallable()),
            O\flatMap(fn(FuncCall $call) => pipe(
                O\bindable(),
                O\bind(
                    source: fn() => pipe(
                        O\some($event->getStatementsSource()),
                        O\filterOf(StatementsAnalyzer::class),
                    ),
                    hof: fn() => pipe(
                        O\some($call),
                        O\flatMap(PsalmApi::$type->get($event)),
                        O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TClosure::class)),
                    ),
                    kindAtomic: fn($i) => pipe(
                        O\fromNullable($i->hof->params[0] ?? null),
                        O\flatMapNullable(fn(FunctionLikeParameter $p) => $p->type),
                        O\flatMap($getKind),
                    ),
                    kindTypeParam: fn($i) => pipe(
                        O\some($i->kindAtomic),
                        O\flatMap($getKindParam),
                    ),
                    definedProperties: fn($i) => pipe(
                        O\some(GetObjectVarsReturnTypeProvider::getGetObjectVarsReturnType(
                            first_arg_type: $i->kindTypeParam,
                            statements_source: $i->source,
                            context: $event->getContext(),
                            location: new CodeLocation($i->source, $call),
                        )),
                        O\map(fn(TArray|TKeyedArray $shape) => $shape instanceof TKeyedArray
                            ? $shape->properties
                            : []),
                    ),
                    propertyNode: fn() => pipe(
                        L\fromIterable($call->getArgs()),
                        L\first(...),
                    ),
                    propertyName: fn($i) => pipe(
                        O\some($i->propertyNode->value),
                        O\flatMap(PsalmApi::$type->get($event)),
                        O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TLiteralString::class)),
                        O\flatMap(fn(TLiteralString $l) => Ev\proveNonEmptyString($l->value)),
                    ),
                ),
                O\tap(fn($i) => pipe(
                    Ev\proveTrue($i->kindTypeParam->isNever()),
                    O\orElse(fn() => pipe(
                        PsalmApi::$codebase->properties->propertyExists(
                            property_id: "{$i->kindTypeParam->getId()}::\${$i->propertyName}",
                            read_mode: true,
                            source: $i->source,
                            code_location: new CodeLocation($i->source, $i->propertyNode),
                        ),
                        Ev\proveTrue(...),
                    )),
                    O\orElse(fn() => pipe(
                        $event,
                        PropertyUndefined::raise($i->propertyName, $i->kindTypeParam),
                    )),
                )),
                O\map(fn($i) => pipe(
                    $i->definedProperties,
                    D\get($i->propertyName),
                    O\map(fn($property) => $mapKindParam($i->kindAtomic, $property)),
                    O\map(fn($inferred) => $i->hof->replace(
                        params: $i->hof->params,
                        return_type: $inferred,
                    )),
                    O\getOrCall(fn() => $i->hof->replace(
                        params: $i->hof->params,
                        return_type: $mapKindParam($i->kindAtomic, PsalmApi::$create->never()),
                    )),
                )),
                O\tap(PsalmApi::$type->set($event->getExpr(), $event)),
                O\map(constVoid(...)),
            )),
        );
    }
}
