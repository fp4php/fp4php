<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\ArrayList;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;

use function array_key_exists;
use function in_array;

// region: constructor

/**
 * Iterates over `iterable<A>` to create `list<A>`.
 *
 * ```php
 * use Fp4\PHP\Module\ArrayList as L;
 *
 * use function Fp4\PHP\Module\Functions\pipe;
 * use function PHPUnit\Framework\assertSame;
 *
 * $gen = function(): iterable {
 *     yield 1;
 *     yield 2;
 *     yield 3;
 * };
 *
 * assertSame([1, 2, 3], pipe(
 *     $gen(),
 *     L\fromIterable(...),
 * ));
 * ```
 *
 * The inferred type will be widened, just like for {@see \Fp4\PHP\Module\ArrayList\from}
 * This behavior is possible due to the next plugin hook: {@see \Fp4\PHP\PsalmIntegration\ArrayList\FromCallWidening}.
 *
 * @template A
 *
 * @param iterable<A> $iter
 * @return list<A>
 * @psalm-return ($iter is non-empty-array<A> ? non-empty-list<A> : list<A>)
 */
function fromIterable(iterable $iter): array
{
    $list = [];

    foreach ($iter as $a) {
        $list[] = $a;
    }

    return $list;
}

/**
 * Helps to infer a widen type from the type A.
 * i.e. for expression `from([1, 2, 3])` Psalm will infer `list<int>`, not list<1|2|3> or list{1, 2, 3}.
 * This behavior is possible due to the next plugin hook: {@see \Fp4\PHP\PsalmIntegration\ArrayList\FromCallWidening}.
 *
 * @template A of list<mixed>
 *
 * @param A $list
 * @return A
 */
function from(array $list): array
{
    return $list;
}

/**
 * Helps to infer a literal type from the type A.
 * i.e. for expression `fromLiteral([1, 2, 3])` Psalm will infer `list{1, 2, 3}`, not `list<1|2|3>` or `list<int>`.
 * If `$list` has non-literal type Psalm triggers `InvalidArgument` issue.
 * This behavior is possible due to the next plugin hook: {@see \Fp4\PHP\PsalmIntegration\ArrayList\FromLiteralCallValidator}.
 *
 * @template A of list<mixed>
 *
 * @param A $list
 * @return A
 */
function fromLiteral(array $list): array
{
    return $list;
}

// endregion: constructor

// region: ops

/**
 * Applies the $callback to each element of the iterable<A> and collects the results in a new list<B>.
 *
 * ```php
 * use Fp4\PHP\Module\ArrayList as L;
 *
 * use function Fp4\PHP\Module\Functions\pipe;
 * use function PHPUnit\Framework\assertSame;
 *
 * assertSame(['1', '2', '3'], pipe(
 *     L\from([1, 2, 3]),
 *     L\map(fn(int $num) => (string) $num),
 * ));
 * ```
 *
 * @template A
 * @template B
 * @template TIn of iterable<A>
 *
 * @param callable(A): B $callback
 * @return Closure(iterable<A>): list<B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<B>
 *         : list<B>
 * ))
 */
function map(callable $callback): Closure
{
    return function(iterable $iter) use ($callback) {
        $list = [];

        foreach ($iter as $a) {
            $list[] = $callback($a);
        }

        return $list;
    };
}

/**
 * @template A
 * @template B
 * @template TIn of iterable<int, A>
 *
 * @param callable(int, A): B $callback
 * @return Closure(iterable<int, A>): list<B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<B>
 *         : list<B>
 * ))
 */
function mapKV(callable $callback): Closure
{
    return function(iterable $iter) use ($callback) {
        $list = [];

        foreach ($iter as $key => $a) {
            $list[] = $callback($key, $a);
        }

        return $list;
    };
}

/**
 * @template A
 * @template B
 * @template TIn of iterable<A>
 *
 * @param callable(A): list<B> $callback
 * @return Closure(iterable<A>): list<B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<B>
 *         : list<B>
 * ))
 */
function flatMap(callable $callback): Closure
{
    return function(iterable $iter) use ($callback) {
        $list = [];

        foreach ($iter as $a) {
            foreach ($callback($a) as $b) {
                $list[] = $b;
            }
        }

        return $list;
    };
}

/**
 * @template A
 * @template B
 * @template TIn of iterable<int, A>
 *
 * @param callable(int, A): list<B> $callback
 * @return Closure(iterable<int, A>): list<B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<B>
 *         : list<B>
 * ))
 */
function flatMapKV(callable $callback): Closure
{
    return function(iterable $iter) use ($callback) {
        $list = [];

        foreach ($iter as $key => $a) {
            foreach ($callback($key, $a) as $b) {
                $list[] = $b;
            }
        }

        return $list;
    };
}

/**
 * @template A
 * @template B
 *
 * @param B $value
 * @return Closure(iterable<A>): non-empty-list<A|B>
 */
function prepend(mixed $value): Closure
{
    return function (iterable $iter) use ($value) {
        $list = [$value];

        foreach ($iter as $a) {
            $list[] = $a;
        }

        return $list;
    };
}

/**
 * @template A
 * @template B
 *
 * @param B $value
 * @return Closure(iterable<A>): non-empty-list<A|B>
 */
function append(mixed $value): Closure
{
    return function (iterable $iter) use ($value) {
        $list = [];

        foreach ($iter as $a) {
            $list[] = $a;
        }

        $list[] = $value;

        return $list;
    };
}

// endregion: ops

// region: terminal ops

/**
 * @return Closure(list<mixed>): bool
 */
function contains(mixed $value): Closure
{
    return fn(array $list) => in_array($value, $list, strict: true);
}

/**
 * @template A
 *
 * @param list<A> $list
 * @return Option<A>
 */
function first(array $list): Option
{
    return array_key_exists(0, $list)
        ? O\some($list[0])
        : O\none;
}

/**
 * @template A
 *
 * @param list<A> $list
 * @return Option<A>
 */
function second(array $list): Option
{
    return array_key_exists(1, $list)
        ? O\some($list[1])
        : O\none;
}

/**
 * @template A
 *
 * @param list<A> $list
 * @return Option<A>
 */
function last(array $list): Option
{
    $lastKey = array_key_last($list) ?? null;

    return null !== $lastKey
        ? O\some($list[$lastKey])
        : O\none;
}

/**
 * @template A
 * @template B
 * @template TIn of iterable<A>
 *
 * @param callable(A): Option<B> $callback
 * @return Closure(iterable<A>): Option<list<B>>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? Option<non-empty-list<B>>
 *         : Option<list<B>>
 * ))
 */
function traverseOption(callable $callback): Closure
{
    return function(iterable $iter) use ($callback) {
        $list = [];

        foreach ($iter as $a) {
            $b = $callback($a);

            if (O\isSome($b)) {
                $list[] = $b->value;
            } else {
                return O\none;
            }
        }

        return O\some($list);
    };
}

// endregion: terminal ops
