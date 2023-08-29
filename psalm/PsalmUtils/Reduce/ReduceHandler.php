<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Reduce;

use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\FunctionType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\ClosureAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\BeforeExpressionAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class ReduceHandler
{
    /**
     * @param non-empty-list<string> $functions
     * @return false|null
     */
    public static function beforeExpressionAnalysis(BeforeExpressionAnalysisEvent $event, array $functions): ?bool
    {
        return pipe(
            pipe(
                O\bindable(),
                O\bind(
                    reduceCall: fn() => pipe(
                        O\some($event->getExpr()),
                        O\filterOf(FuncCall::class),
                        O\filter(fn(FuncCall $call) => pipe(
                            $functions,
                            L\contains($call->name->getAttribute('resolvedName')),
                        )),
                    ),
                    functionType: fn($i) => pipe(
                        Ev\proveString($i->reduceCall->name->getAttribute('resolvedName')),
                        O\map(fn(string $function) => str_ends_with($function, 'KV')
                            ? FunctionType::KeyValue
                            : FunctionType::Value),
                    ),
                    reduceArgs: fn($i) => O\some($i->reduceCall->getArgs()),
                    initialArg: fn($i) => pipe(
                        $i->reduceArgs,
                        D\get(0),
                        O\tap(fn(Arg $initial) => $initial->value->setAttribute('widenTypeAfterAnalysis', true)),
                    ),
                    reducerArg: fn($i) => pipe(
                        $i->reduceArgs,
                        D\get(1),
                        O\tap(fn(Arg $reducer) => $reducer->value->setAttribute('returnTypeIsAssignableTo', $i->initialArg->value)),
                        O\tap(fn(Arg $reducer) => $reducer->value->setAttribute('functionType', $i->functionType)),
                    ),
                ),
            ),
            constNull(...),
        );
    }

    /**
     * @return false|null
     */
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $widenInitial = fn(): Option => pipe(
            O\some($event->getExpr()),
            O\filter(fn(Expr $expr) => $expr->hasAttribute('widenTypeAfterAnalysis')),
            O\flatMap(fn(Expr $expr) => pipe(
                O\some($expr),
                O\flatMap(PsalmApi::$type->get($event)),
                O\map(PsalmApi::$cast->toNonLiteralType(...)),
                O\tap(PsalmApi::$type->set($expr, $event)),
                O\map(fn() => $expr),
            )),
            O\tap(fn($expr) => $expr->setAttribute('widenTypeAfterAnalysis', null)),
        );

        $widenReducer = fn(): Option => pipe(
            O\bindable(),
            O\bind(
                reducerExpr: fn() => pipe(
                    $event->getExpr(),
                    Ev\proveOf([
                        Expr\Closure::class,
                        Expr\ArrowFunction::class,
                    ]),
                ),
                functionType: fn($i) => pipe(
                    $i->reducerExpr->getAttribute('functionType'),
                    Ev\proveOf(FunctionType::class),
                ),
                initialExpr: fn($i) => pipe(
                    $i->reducerExpr->getAttribute('returnTypeIsAssignableTo'),
                    Ev\proveOf(Expr::class),
                ),
                initialType: fn($i) => pipe(
                    $i->initialExpr,
                    PsalmApi::$type->get($event),
                ),
                reducerType: fn($i) => pipe(
                    O\some($i->reducerExpr),
                    O\flatMap(PsalmApi::$type->get($event)),
                    O\flatMap(PsalmApi::$cast->toSingleAtomicOf([
                        TCallable::class,
                        TClosure::class,
                    ])),
                ),
                reducerParams: fn($i) => O\fromNullable($i->reducerType->params),
                accamulator: fn($i) => L\first($i->reducerParams),
                reducerReturn: fn($i) => O\fromNullable($i->reducerType->return_type),
            ),
            O\let(
                nonLiteralReducerReturn: fn($i) => PsalmApi::$cast->toNonLiteralType($i->reducerReturn),
                combinedAccumulatorWithInitial: fn($i) => PsalmApi::$type->combine(
                    $i->accamulator->type ?? Type::getMixed(),
                    $i->nonLiteralReducerReturn,
                ),
                replacedReducer: fn($i) => $i->reducerType->replace(
                    params: [
                        $i->accamulator->setType($i->combinedAccumulatorWithInitial),
                        ...L\tail($i->reducerType->params ?? []),
                    ],
                    return_type: $i->nonLiteralReducerReturn,
                ),
            ),
            O\tap(fn($i) => pipe(
                $i->replacedReducer,
                PsalmApi::$type->set($i->reducerExpr, $event),
            )),
            O\tap(fn($i) => O\first(
                fn() => self::handleEmptyArrayInitial($i->initialExpr, $i->initialType, $i->nonLiteralReducerReturn, $event),
                fn() => self::checkReducerReturnType($i->initialType, $i->nonLiteralReducerReturn, $event),
            )),
            O\tap(function($i): void {
                $i->reducerExpr->setAttribute('returnTypeIsAssignableTo', null);
            }),
        );

        return pipe($widenInitial(), O\orElse($widenReducer), constNull(...));
    }

    private static function handleEmptyArrayInitial(
        Expr $initialExpr,
        Union $initialType,
        Union $reducerReturn,
        AfterExpressionAnalysisEvent $event,
    ): Option {
        return pipe(
            Ev\proveTrue($initialType->isEmptyArray()),
            O\flatMap(fn() => pipe(
                L\fromIterable($reducerReturn->getAtomicTypes()),
                L\all(fn(Type\Atomic $a) => $a instanceof TArray || $a instanceof TKeyedArray),
                Ev\proveTrue(...),
            )),
            O\tap(fn() => pipe(
                $reducerReturn,
                PsalmApi::$type->set($initialExpr, $event),
            )),
        );
    }

    private static function checkReducerReturnType(
        Union $initialType,
        Union $reducerReturnType,
        AfterExpressionAnalysisEvent $event,
    ): Option {
        $codebase = $event->getCodebase();
        $source = $event->getStatementsSource();

        return pipe(
            Ev\proveFalse($codebase->isTypeContainedByType($reducerReturnType, $initialType)),
            O\map(fn() => new InvalidArgument(
                message: "Type {$reducerReturnType->getId()} is not assignable to {$initialType->getId()}",
                code_location: new CodeLocation($source, $event->getExpr()),
            )),
            O\tap(fn($e) => IssueBuffer::maybeAdd($e, $source->getSuppressedIssues())),
        );
    }
}
