<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Node\Expr\VirtualArrowFunction;
use Psalm\Node\Expr\VirtualFuncCall;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualStaticCall;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\VirtualArg;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TNamedObject;

use function Fp4\PHP\Module\Functions\pipe;

final class FirstClassCallablePredicate
{
    /**
     * @return Option<VirtualArrowFunction>
     */
    public static function mock(AfterExpressionAnalysisEvent $e): Option
    {
        return pipe(
            O\bindable(),
            O\bind(
                variables: fn() => pipe(
                    $e->getExpr(),
                    Ev\proveOf(FuncCall::class),
                    O\map(fn(FuncCall $call): mixed => $call->name->getAttribute('resolvedName')),
                    O\flatMap(Ev\proveString(...)),
                    O\map(fn($functionId) => preg_match('/^.+kv$/mi', $functionId)
                        ? [new VirtualVariable('key'), new VirtualVariable('val')]
                        : [new VirtualVariable('val')]),
                ),
                callArgs: fn() => pipe(
                    $e->getExpr(),
                    Ev\proveOf(FuncCall::class),
                    O\filter(fn(FuncCall $call) => !$call->isFirstClassCallable()),
                    O\map(fn(FuncCall $call) => $call->getArgs()),
                ),
            ),
            O\flatMap(fn($i) => pipe(
                L\fromIterable($i->callArgs),
                L\first(...),
                O\map(fn(Arg $arg) => $arg->value),
                O\flatMap(Ev\proveOf([FuncCall::class, MethodCall::class, StaticCall::class])),
                O\filter(fn(CallLike $call) => $call->isFirstClassCallable()),
                O\flatMap(fn(CallLike $call) => self::createVirtualCall(
                    source: $e->getStatementsSource(),
                    context: $e->getContext(),
                    originalCall: $call,
                    fakeVariables: $i->variables,
                )),
                O\map(fn(CallLike $call) => new VirtualArrowFunction([
                    'expr' => $call,
                    'params' => pipe(
                        $i->variables,
                        L\map(fn(VirtualVariable $var) => new Param($var)),
                    ),
                ])),
            )),
        );
    }

    /**
     * @param FuncCall|MethodCall|StaticCall $originalCall
     * @param list<VirtualVariable> $fakeVariables
     * @return Option<CallLike>
     */
    private static function createVirtualCall(
        StatementsSource $source,
        Context $context,
        CallLike $originalCall,
        array $fakeVariables,
    ): Option {
        $functionId = O\first(
            // from FuncCall
            fn() => pipe(
                $originalCall,
                Ev\proveOf(FuncCall::class),
                O\flatMap(fn(FuncCall $call) => pipe(
                    $call->name->getAttribute('resolvedName'),
                    O\fromNullable(...),
                    O\orElse(fn() => pipe(
                        $call->name,
                        Ev\proveOf(Name::class),
                        O\map(fn(Name $name) => $name->toString()),
                    )),
                    O\flatMap(Ev\proveNonEmptyString(...)),
                )),
            ),
            // or from MethodCall
            fn() => pipe(
                O\bindable(),
                O\bind(
                    call: fn() => pipe(
                        $originalCall,
                        Ev\proveOf(MethodCall::class),
                    ),
                    class: fn($i) => pipe(
                        $i->call->var,
                        PsalmApi::$types->getExprType($source),
                        O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
                        O\flatMap(Ev\proveOf(TNamedObject::class)),
                        O\map(fn(TNamedObject $object) => $object->value),
                    ),
                    method: fn($i) => pipe(
                        $i->call->name,
                        Ev\proveOf(Identifier::class),
                        O\map(fn(Identifier $id) => $id->toString()),
                    ),
                ),
                O\map(fn($i) => "{$i->class}::{$i->method}"),
            ),
            // or from StaticCall
            fn() => pipe(
                O\bindable(),
                O\bind(
                    call: fn() => pipe(
                        $originalCall,
                        Ev\proveOf(StaticCall::class),
                    ),
                    class: fn($i) => pipe(
                        $i->call->class,
                        Ev\proveOf(Name::class),
                        O\map(fn(Name $name) => $name->toString()),
                        O\map(fn(string $name) => match ($name) {
                            'self', 'static', 'parent' => $context->self,
                            default => $name,
                        }),
                    ),
                    method: fn($i) => pipe(
                        $i->call->name,
                        Ev\proveOf(Identifier::class),
                        O\map(fn(Identifier $id) => $id->toString()),
                    ),
                ),
                O\map(fn($i) => "{$i->class}::{$i->method}"),
            ),
        );

        $args = pipe(
            $fakeVariables,
            L\map(fn(VirtualVariable $v) => new VirtualArg($v)),
        );

        return pipe(
            $functionId,
            O\flatMap(
                fn($id) => self::withCustomAssertions($id, $source, match (true) {
                    $originalCall instanceof FuncCall => new VirtualFuncCall($originalCall->name, $args),
                    $originalCall instanceof MethodCall => new VirtualMethodCall($originalCall->var, $originalCall->name, $args),
                    $originalCall instanceof StaticCall => new VirtualStaticCall($originalCall->class, $originalCall->name, $args),
                }),
            ),
        );
    }

    /**
     * @param non-empty-string $functionId
     * @param VirtualStaticCall|VirtualMethodCall|VirtualFuncCall $expr
     * @return Option<CallLike>
     */
    private static function withCustomAssertions(string $functionId, StatementsSource $source, CallLike $expr): Option
    {
        return pipe(
            O\bindable(),
            O\bind(
                analyzer: fn() => pipe($source, Ev\proveOf(StatementsAnalyzer::class)),
                storage: fn($i) => O\first(
                    fn() => O\when(
                        PsalmApi::$codebase->functions->functionExists($i->analyzer, strtolower($functionId)),
                        fn() => PsalmApi::$codebase->functions->getStorage($i->analyzer, strtolower($functionId)),
                    ),
                    fn() => O\when(
                        PsalmApi::$codebase->methods->hasStorage(MethodIdentifier::wrap($functionId)),
                        fn() => PsalmApi::$codebase->methods->getStorage(MethodIdentifier::wrap($functionId)),
                    ),
                ),
            ),
            O\tap(fn($i) => $i->analyzer->node_data->setIfTrueAssertions($expr, $i->storage->if_true_assertions)),
            O\tap(fn($i) => $i->analyzer->node_data->setIfFalseAssertions($expr, $i->storage->if_false_assertions)),
            O\map(fn() => $expr),
        );
    }
}
