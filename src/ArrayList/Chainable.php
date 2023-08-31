<?php

declare(strict_types=1);

namespace Fp4\PHP\ArrayList;

use Closure;

use function array_slice;

/**
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(A): B $callback
 * @return (Closure(TIn): (
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
 * @param callable(int, A): B $callback
 * @return (Closure(TIn): (
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
 * @param callable(A): void $callback
 * @return (Closure(TIn): (
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
 * @param callable(int, A): void $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? non-empty-list<A>
 *         : list<A>
 * ))
 */
function tapKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        foreach ($list as $key => $a) {
            $callback($key, $a);
        }

        return $list;
    };
}

/**
 * @template A
 * @template B
 * @template TIn of list<A>
 * @template TFlatten of array<B>
 *
 * @param callable(A): TFlatten $callback
 * @return (Closure(TIn): (
 *    TIn is non-empty-list<A>
 *        ? (TFlatten is non-empty-array<B> ? non-empty-list<B> : list<B>)
 *        : (list<B>)
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
 * @template TFlatten of iterable<B>
 *
 * @param callable(int, A): TFlatten $callback
 * @return (Closure(TIn): (
 *    TIn is non-empty-list<A>
 *        ? (TFlatten is non-empty-array<B> ? non-empty-list<B> : list<B>)
 *        : (list<B>)
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
 *
 * @param callable(int, A): bool $callback
 * @return Closure(list<A>): list<A>
 */
function filterKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $key => $item) {
            if ($callback($key, $item)) {
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

/**
 * @template A
 *
 * @param list<A> $list
 * @return list<A>
 */
function tail(array $list): array
{
    return array_slice($list, offset: 1);
}
