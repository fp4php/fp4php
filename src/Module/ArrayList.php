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
 * use function Fp4\PHP\Module\Psalm\assertType;
 * use function PHPUnit\Framework\assertSame;
 *
 * $gen = function(): iterable {
 *     yield 1;
 *     yield 2;
 *     yield 3;
 * };
 *
 * $list = L\fromIterable($gen());
 *
 * assertSame([1, 2, 3], $list);
 * assertType('list<int>', $list);
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
 *
 * ```php
 * use Fp4\PHP\Module\ArrayList as L;
 *
 * use function Fp4\PHP\Module\Functions\pipe;
 * use function Fp4\PHP\Module\Psalm\assertType;
 * use function PHPUnit\Framework\assertSame;
 *
 * assertSame([1, 2, 3], L\from([1, 2, 3]));
 * assertType('list<int>', L\from([1, 2, 3]))
 * ```
 *
 * i.e. for expression `L\from([1, 2, 3])` Psalm will infer `list<int>`, not list<1|2|3> or list{1, 2, 3}.
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
 *
 * ```php
 * use Fp4\PHP\Module\ArrayList as L;
 *
 * use function Fp4\PHP\Module\Functions\pipe;
 * use function Fp4\PHP\Module\Psalm\assertType;
 * use function PHPUnit\Framework\assertSame;
 *
 * assertSame([1, 2, 3], L\fromLiteral([1, 2, 3]));
 * assertType('list{1, 2, 3}', L\fromLiteral([1, 2, 3]))
 * ```
 *
 * i.e. for expression `L\fromLiteral([1, 2, 3])` Psalm will infer `list{1, 2, 3}`, not `list<1|2|3>` or `list<int>`.
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
 * Applies the $callback to each element of the list<A> and collects the results in a new list<B>.
 *
 * ```php
 * use Fp4\PHP\Module\ArrayList as L;
 *
 * use function Fp4\PHP\Module\Functions\pipe;
 * use function Fp4\PHP\Module\Psalm\assertType;
 * use function PHPUnit\Framework\assertSame;
 *
 * $list = pipe(
 *     L\from([1, 2, 3]),
 *     L\map(fn(int $num) => (string) $num),
 * );
 *
 * assertSame(['1', '2', '3'], $list);
 * assertType('list<int>', $list)
 * ```
 *
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(A): B $callback
 * @return Closure(list<A>): list<B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<B>
 *         : list<B>
 * ))
 */
function map(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $a) {
            $out[] = $callback($a);
        }

        return $out;
    };
}

/**
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(A): void $callback
 * @return Closure(list<A>): list<A>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<A>
 *         : list<A>
 * ))
 */
function tap(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        foreach ($list as $a) {
            $callback($a);
        }

        return $list;
    };
}

/**
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(int, A): B $callback
 * @return Closure(list<A>): list<B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<B>
 *         : list<B>
 * ))
 */
function mapKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $key => $a) {
            $out[] = $callback($key, $a);
        }

        return $out;
    };
}

/**
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(A): list<B> $callback
 * @return Closure(list<A>): list<B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<B>
 *         : list<B>
 * ))
 */
function flatMap(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $a) {
            foreach ($callback($a) as $b) {
                $out[] = $b;
            }
        }

        return $out;
    };
}

/**
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(int, A): list<B> $callback
 * @return Closure(list<A>): list<B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<B>
 *         : list<B>
 * ))
 */
function flatMapKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $key => $a) {
            foreach ($callback($key, $a) as $b) {
                $out[] = $b;
            }
        }

        return $out;
    };
}

/**
 * @template A
 *
 * @param callable(A): bool $callback
 * @return Closure(list<A>): list<A>
 */
function filter(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $item) {
            if ($callback($item)) {
                $out[] = $item;
            }
        }

        return $out;
    };
}

/**
 * @template A
 * @template B
 *
 * @param B $value
 * @return Closure(list<A>): non-empty-list<A|B>
 */
function prepend(mixed $value): Closure
{
    return function(array $list) use ($value) {
        $out = [$value];

        foreach ($list as $a) {
            $out[] = $a;
        }

        return $out;
    };
}

/**
 * @template A
 * @template B
 *
 * @param B $value
 * @return Closure(list<A>): non-empty-list<A|B>
 */
function append(mixed $value): Closure
{
    return function(array $list) use ($value) {
        $out = [];

        foreach ($list as $a) {
            $out[] = $a;
        }

        $out[] = $value;

        return $out;
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
function third(array $list): Option
{
    return array_key_exists(2, $list)
        ? O\some($list[2])
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
 * @template TIn of list<A>
 *
 * @param callable(A): Option<B> $callback
 * @return Closure(list<A>): Option<list<B>>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? Option<non-empty-list<B>>
 *         : Option<list<B>>
 * ))
 */
function traverseOption(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $a) {
            $b = $callback($a);

            if (O\isNone($b)) {
                return O\none;
            }

            $out[] = $b->value;
        }

        return O\some($out);
    };
}

// endregion: terminal ops
