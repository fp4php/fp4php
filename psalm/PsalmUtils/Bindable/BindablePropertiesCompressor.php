<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable;

use Closure;
use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Evidence as Ev;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Expr\FuncCall;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\constVoid;
use function Fp4\PHP\Combinator\pipe;

final class BindablePropertiesCompressor
{
    /**
     * @param non-empty-list<string> $functions
     * @param callable(Union): O\Option<Union> $unpack
     * @param callable(non-empty-array<Union>, Union): O\Option<Union> $pack
     * @return Closure(AfterExpressionAnalysisEvent): O\Option<void>
     */
    public static function compress(array $functions, callable $unpack, callable $pack): Closure
    {
        return fn(AfterExpressionAnalysisEvent $event) => pipe(
            O\some($event->getExpr()),
            O\filterOf(FuncCall::class),
            O\filter(fn(FuncCall $call) => pipe(
                L\from($functions),
                L\contains($call->name->getAttribute('resolvedName')),
            )),
            O\flatMap(PsalmApi::$type->get($event)),
            O\flatMap(PsalmApi::$cast->toSingleAtomic(...)),
            O\filterOf(TClosure::class),
            O\flatMap(self::inferClosure($unpack, $pack)),
            O\tap(PsalmApi::$type->set($event->getExpr(), $event)),
            O\map(constVoid(...)),
        );
    }

    /**
     * @param callable(Union): O\Option<Union> $unpack
     * @param callable(non-empty-array<Union>, Union): O\Option<Union> $pack
     * @return Closure(TClosure): O\Option<TClosure>
     */
    private static function inferClosure(callable $unpack, callable $pack): Closure
    {
        return fn(TClosure $returnType): O\Option => pipe(
            O\bindable(),
            O\bind(
                original: fn() => O\fromNullable($returnType->return_type),
                bindable: fn($i) => $unpack($i->original),
                properties: fn($i) => pipe(
                    O\some($i->bindable),
                    O\flatMap(BindableFoldType::for(...)),
                    O\flatMap(Ev\proveNonEmptyArray(...)),
                ),
            ),
            O\flatMap(fn($i) => $pack($i->properties, $i->original)),
            O\map(fn($packed) => $returnType->replace(
                params: $returnType->params,
                return_type: $packed,
            )),
        );
    }
}
