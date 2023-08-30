<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Fold;

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

final class FoldHandler
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
                    foldCall: fn() => pipe(
                        O\some($event->getExpr()),
                        O\filterOf(FuncCall::class),
                        O\filter(fn(FuncCall $call) => pipe(
                            $functions,
                            L\contains($call->name->getAttribute('resolvedName')),
                        )),
                    ),
                    functionType: fn($i) => pipe(
                        Ev\proveString($i->foldCall->name->getAttribute('resolvedName')),
                        O\map(fn(string $function) => str_ends_with($function, 'KV')
                            ? FunctionType::KeyValue
                            : FunctionType::Value),
                    ),
                    foldArgs: fn($i) => O\some($i->foldCall->getArgs()),
                    initialArg: fn($i) => pipe(
                        $i->foldArgs,
                        D\get(0),
                        O\tap(fn(Arg $initial) => $initial->value->setAttribute('widenTypeAfterAnalysis', true)),
                    ),
                    foldFnArg: fn($i) => pipe(
                        $i->foldArgs,
                        D\get(1),
                        O\tap(fn(Arg $foldFn) => $foldFn->value->setAttribute('returnTypeIsAssignableTo', $i->initialArg->value)),
                        O\tap(fn(Arg $foldFn) => $foldFn->value->setAttribute('functionType', $i->functionType)),
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

        $widenfoldFn = fn(): Option => pipe(
            O\bindable(),
            O\bind(
                foldFnExpr: fn() => pipe(
                    $event->getExpr(),
                    Ev\proveOf([
                        Expr\Closure::class,
                        Expr\ArrowFunction::class,
                    ]),
                ),
                functionType: fn($i) => pipe(
                    $i->foldFnExpr->getAttribute('functionType'),
                    Ev\proveOf(FunctionType::class),
                ),
                initialExpr: fn($i) => pipe(
                    $i->foldFnExpr->getAttribute('returnTypeIsAssignableTo'),
                    Ev\proveOf(Expr::class),
                ),
                initialType: fn($i) => pipe(
                    $i->initialExpr,
                    PsalmApi::$type->get($event),
                ),
                foldFnType: fn($i) => pipe(
                    O\some($i->foldFnExpr),
                    O\flatMap(PsalmApi::$type->get($event)),
                    O\flatMap(PsalmApi::$cast->toSingleAtomicOf([
                        TCallable::class,
                        TClosure::class,
                    ])),
                ),
                foldFnParams: fn($i) => O\fromNullable($i->foldFnType->params),
                accamulator: fn($i) => L\first($i->foldFnParams),
                foldFnReturn: fn($i) => O\fromNullable($i->foldFnType->return_type),
            ),
            O\let(
                nonLiteralfoldFnReturn: fn($i) => PsalmApi::$cast->toNonLiteralType($i->foldFnReturn),
                combinedAccumulatorWithInitial: fn($i) => PsalmApi::$type->combine(
                    $i->accamulator->type ?? Type::getMixed(),
                    $i->nonLiteralfoldFnReturn,
                ),
                replacedfoldFn: fn($i) => $i->foldFnType->replace(
                    params: [
                        $i->accamulator->setType($i->combinedAccumulatorWithInitial),
                        ...L\tail($i->foldFnType->params ?? []),
                    ],
                    return_type: $i->nonLiteralfoldFnReturn,
                ),
            ),
            O\tap(fn($i) => pipe(
                $i->replacedfoldFn,
                PsalmApi::$type->set($i->foldFnExpr, $event),
            )),
            O\tap(fn($i) => O\first(
                fn() => self::handleEmptyArrayInitial($i->initialExpr, $i->initialType, $i->nonLiteralfoldFnReturn, $event),
                fn() => self::checkFoldFnReturnType($i->initialType, $i->nonLiteralfoldFnReturn, $event),
            )),
            O\tap(function($i): void {
                $i->foldFnExpr->setAttribute('returnTypeIsAssignableTo', null);
            }),
        );

        return pipe($widenInitial(), O\orElse($widenfoldFn), constNull(...));
    }

    private static function handleEmptyArrayInitial(
        Expr $initialExpr,
        Union $initialType,
        Union $foldFnReturn,
        AfterExpressionAnalysisEvent $event,
    ): Option {
        return pipe(
            Ev\proveTrue($initialType->isEmptyArray()),
            O\flatMap(fn() => pipe(
                L\fromIterable($foldFnReturn->getAtomicTypes()),
                L\all(fn(Type\Atomic $a) => $a instanceof TArray || $a instanceof TKeyedArray),
                Ev\proveTrue(...),
            )),
            O\tap(fn() => pipe(
                $foldFnReturn,
                PsalmApi::$type->set($initialExpr, $event),
            )),
        );
    }

    private static function checkFoldFnReturnType(
        Union $initialType,
        Union $foldFnReturnType,
        AfterExpressionAnalysisEvent $event,
    ): Option {
        $codebase = $event->getCodebase();
        $source = $event->getStatementsSource();

        return pipe(
            Ev\proveFalse($codebase->isTypeContainedByType($foldFnReturnType, $initialType)),
            O\map(fn() => new InvalidArgument(
                message: "Type {$foldFnReturnType->getId()} is not assignable to {$initialType->getId()}",
                code_location: new CodeLocation($source, $event->getExpr()),
            )),
            O\tap(fn($e) => IssueBuffer::maybeAdd($e, $source->getSuppressedIssues())),
        );
    }
}
