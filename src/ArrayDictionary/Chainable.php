<?php

declare(strict_types=1);

namespace Fp4\PHP\ArrayDictionary;

use Closure;

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of array<K, A>
 *
 * @param callable(A): B $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? non-empty-array<K, B>
 *         : array<K, B>
 * ))
 */
function map(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $v) {
            $out[$k] = $callback($v);
        }

        return $out;
    };
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of array<K, A>
 *
 * @param callable(K, A): B $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? non-empty-array<K, B>
 *         : array<K, B>
 * ))
 */
function mapKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $v) {
            $out[$k] = $callback($k, $v);
        }

        return $out;
    };
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of array<K, A>
 *
 * @param callable(A): B $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? non-empty-array<K, A>
 *         : array<K, A>
 * ))
 */
function tap(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        foreach ($dictionary as $v) {
            $callback($v);
        }

        return $dictionary;
    };
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of array<K, A>
 *
 * @param callable(K, A): B $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? non-empty-array<K, A>
 *         : array<K, A>
 * ))
 */
function tapKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        foreach ($dictionary as $k => $v) {
            $callback($k, $v);
        }

        return $dictionary;
    };
}

/**
 * @template KA of array-key
 * @template A
 * @template KB of array-key
 * @template B
 * @template TIn of array<KA, A>
 * @template TFlatten of array<KB, B>
 *
 * @param callable(A): TFlatten $callback
 * @return (Closure(TIn): (
 *    TIn is non-empty-array<KA, A>
 *        ? (TFlatten is non-empty-array<KB, B> ? non-empty-array<KB, B> : array<KB, B>)
 *        : (array<KB, B>)
 * ))
 */
function flatMap(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $nested) {
            foreach ($callback($nested) as $k => $v) {
                $out[$k] = $v;
            }
        }

        return $out;
    };
}

/**
 * @template KA of array-key
 * @template A
 * @template KB of array-key
 * @template B
 * @template TIn of array<KA, A>
 * @template TFlatten of array<KB, B>
 *
 * @param callable(KA, A): TFlatten $callback
 * @return (Closure(TIn): (
 *    TIn is non-empty-array<KA, A>
 *        ? (TFlatten is non-empty-array<KB, B> ? non-empty-array<KB, B> : array<KB, B>)
 *        : (array<KB, B>)
 * ))
 */
function flatMapKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $nestedK => $nestedV) {
            foreach ($callback($nestedK, $nestedV) as $k => $v) {
                $out[$k] = $v;
            }
        }

        return $out;
    };
}

/**
 * @template K of array-key
 * @template A
 *
 * @param callable(A): bool $callback
 * @return Closure(array<K, A>): array<K, A>
 */
function filter(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $v) {
            if ($callback($v)) {
                $out[$k] = $v;
            }
        }

        return $out;
    };
}

/**
 * @template K of array-key
 * @template A
 *
 * @param callable(K, A): bool $callback
 * @return Closure(array<K, A>): array<K, A>
 */
function filterKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $v) {
            if ($callback($k, $v)) {
                $out[$k] = $v;
            }
        }

        return $out;
    };
}

/**
 * @template K of array-key
 * @template V
 * @template TIn of array<K, V>
 * @template TIndex of array-key
 *
 * @param callable(V): TIndex $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, V>
 *         ? non-empty-array<TIndex, V>
 *         : array<TIndex, V>
 * ))
 */
function reindex(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $v) {
            $out[$callback($v)] = $v;
        }

        return $out;
    };
}

/**
 * @template K of array-key
 * @template V
 * @template TIn of array<K, V>
 * @template TIndex of array-key
 *
 * @param callable(K, V): TIndex $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, V>
 *         ? non-empty-array<TIndex, V>
 *         : array<TIndex, V>
 * ))
 */
function reindexKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $v) {
            $out[$callback($k, $v)] = $v;
        }

        return $out;
    };
}

/**
 * @template K1 of array-key
 * @template K2 of array-key
 * @template A
 * @template B
 *
 * @param K2 $key
 * @param B $value
 * @return Closure(array<K1, A>): non-empty-array<K1|K2, A|B>
 */
function prepend(mixed $key, mixed $value): Closure
{
    return fn(array $dictionary) => [$key => $value, ...$dictionary];
}

/**
 * @template K1 of array-key
 * @template K2 of array-key
 * @template A
 * @template B
 *
 * @param K2 $key
 * @param B $value
 * @return Closure(array<K1, A>): non-empty-array<K1|K2, A|B>
 */
function append(mixed $key, mixed $value): Closure
{
    return fn(array $dictionary) => [...$dictionary, $key => $value];
}
