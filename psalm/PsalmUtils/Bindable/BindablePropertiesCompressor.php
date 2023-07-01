<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr\FuncCall;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constVoid;
use function Fp4\PHP\Module\Functions\pipe;

final class BindablePropertiesCompressor
{
    /**
     * @param non-empty-list<string> $functions
     * @param callable(Union): Option<Union> $unpack
     * @param callable(non-empty-array<Union>, Union): Option<Union> $pack
     * @return Closure(AfterExpressionAnalysisEvent): Option<void>
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
     * @param callable(Union): Option<Union> $unpack
     * @param callable(non-empty-array<Union>, Union): Option<Union> $pack
     * @return Closure(TClosure): Option<TClosure>
     */
    private static function inferClosure(callable $unpack, callable $pack): Closure
    {
        return fn(TClosure $returnType): Option => pipe(
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
