<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Option;

use Closure;
use Fp4\PHP\Module\Evidence as E;
use Fp4\PHP\Type\Option;

use function Fp4\PHP\Module\Combinator\pipe;

/**
 * @template A
 * @template B
 *
 * @param callable(A): B $callback
 * @return Closure(Option<A>): Option<B>
 */
function map(callable $callback): Closure
{
    return fn(Option $option) => isSome($option)
        ? some($callback($option->value))
        : none();
}

/**
 * @template A
 *
 * @param callable(A): void $callback
 * @return Closure(Option<A>): Option<A>
 */
function tap(callable $callback): Closure
{
    return function(Option $option) use ($callback) {
        if (isSome($option)) {
            $callback($option->value);

            return some($option->value);
        }

        return none();
    };
}

/**
 * @template A
 * @template B
 *
 * @param callable(A): Option<B> $callback
 * @return Closure(Option<A>): Option<B>
 */
function flatMap(callable $callback): Closure
{
    return fn(Option $option) => isSome($option)
        ? $callback($option->value)
        : none();
}

/**
 * @template A
 * @template B
 *
 * @param callable(A): (null|B) $callback
 * @return Closure(Option<A>): Option<B>
 */
function flatMapNullable(callable $callback): Closure
{
    return fn(Option $option) => isSome($option)
        ? fromNullable($callback($option->value))
        : none();
}

/**
 * @template A
 *
 * @param Option<Option<A>> $option
 * @return Option<A>
 */
function flatten(Option $option): Option
{
    return isSome($option) ? $option->value : none();
}

/**
 * @template A
 * @template B
 *
 * @param Option<A> $fa
 * @return Closure(Option<Closure(A): B>): Option<B>
 */
function ap(Option $fa): Closure
{
    return fn(Option $fab) => match (true) {
        isSome($fa) => match (true) {
            isSome($fab) => some(($fab->value)($fa->value)),
            default => none(),
        },
        default => none(),
    };
}

/**
 * @template A
 * @template B
 *
 * @param callable(): Option<B> $callback
 * @return Closure(Option<A>): Option<A|B>
 */
function orElse(callable $callback): Closure
{
    return fn(Option $option) => isSome($option)
        ? some($option->value)
        : $callback();
}

/**
 * @template A
 *
 * @param callable(A): bool $callback
 * @return Closure(Option<A>): Option<A>
 */
function filter(callable $callback): Closure
{
    return fn(Option $a) => isSome($a) && $callback($a->value)
        ? some($a->value)
        : none();
}

/**
 * @template A
 * @template B
 *
 * @param class-string<B>|non-empty-list<class-string<B>> $fqcn
 * @return Closure(Option<A>): Option<B>
 */
function filterOf(string|array $fqcn, bool $invariant = false): Closure
{
    return fn(Option $a) => pipe(
        $a,
        flatMap(E\proveOf($fqcn, $invariant)),
    );
}

// endregion: ops
