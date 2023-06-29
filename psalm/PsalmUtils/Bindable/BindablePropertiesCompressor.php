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
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;
use function Fp4\PHP\Module\Functions\constVoid;
use function Fp4\PHP\Module\Functions\pipe;

final class BindablePropertiesCompressor
{
    /**
     * @param non-empty-list<string> $functions
     * @param callable(TGenericObject): Option<Union> $extractBindable
     * @param callable(non-empty-array<Union>, TGenericObject): Union $packBindable
     * @return Closure(AfterExpressionAnalysisEvent): Option<void>
     */
    public static function compress(
        array $functions,
        callable $extractBindable,
        callable $packBindable,
    ): Closure {
        return fn(AfterExpressionAnalysisEvent $event) => pipe(
            O\some($event->getExpr()),
            O\filterOf(FuncCall::class),
            O\filter(fn(FuncCall $call) => pipe(
                L\from($functions),
                L\contains($call->name->getAttribute('resolvedName')),
            )),
            O\flatMap(PsalmApi::$types->getExprType($event)),
            O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
            O\filterOf(TClosure::class),
            O\flatMap(self::inferClosure($extractBindable, $packBindable)),
            O\tap(PsalmApi::$types->setExprType($event->getExpr(), $event)),
            O\map(constVoid(...)),
        );
    }

    /**
     * @param callable(TGenericObject): Option<Union> $extractBindable
     * @param callable(non-empty-array<Union>, TGenericObject): Union $packBindable
     * @return Closure(TClosure): Option<TClosure>
     */
    private static function inferClosure(callable $extractBindable, callable $packBindable): Closure
    {
        return fn(TClosure $returnType): Option => pipe(
            O\bindable(),
            O\bind(
                original: fn() => pipe(
                    O\fromNullable($returnType->return_type),
                    O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
                    O\filterOf(TGenericObject::class),
                ),
                bindable: fn($i) => $extractBindable($i->original),
                properties: fn($i) => pipe(
                    O\some($i->bindable),
                    O\flatMap(BindableFoldType::for(...)),
                    O\flatMap(Ev\proveNonEmptyArray(...)),
                ),
            ),
            O\map(fn($i) => $returnType->replace(
                params: $returnType->params,
                return_type: $packBindable($i->properties, $i->original),
            )),
        );
    }
}
