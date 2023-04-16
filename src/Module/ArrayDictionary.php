<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\ArrayDictionary;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;

use function array_key_exists;

// region: constructor

/**
 * @template K of array-key
 * @template A
 *
 * @param iterable<K, A> $iterable
 * @return array<K, A>
 * @psalm-return ($iterable is non-empty-array<K, A> ? non-empty-array<K, A> : array<K, A>)
 */
function fromIterable(iterable $iterable): array
{
    $dictionary = [];

    foreach ($iterable as $k => $v) {
        $dictionary[$k] = $v;
    }

    return $dictionary;
}

/**
 * @template K of array-key
 * @template A
 *
 * @param array<K, A> $dictionary
 * @return array<K, A>
 * @psalm-return ($dictionary is non-empty-array<K, A> ? non-empty-array<K, A> : array<K, A>)
 */
function from(array $dictionary): array
{
    return $dictionary;
}

// endregion: constructor

// region: ops

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of array<K, A>
 *
 * @param callable(A): B $callback
 * @return Closure(array<K, A>): array<K, B>
 * @psalm-return (Closure(TIn): (
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
 * @return Closure(array<K, A>): array<K, B>
 * @psalm-return (Closure(TIn): (
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
 * @param callable(A): array<K, B> $callback
 * @return Closure(array<K, A>): array<K, B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? non-empty-array<K, B>
 *         : array<K, B>
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
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of array<K, A>
 *
 * @param callable(K, A): array<K, B> $callback
 * @return Closure(array<K, A>): array<K, B>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? non-empty-array<K, B>
 *         : array<K, B>
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
 * @return Closure(array<K, A>): Option<A>
 */
function get(int|string $key): Closure
{
    return fn(array $dictionary) => O\fromNullable($dictionary[$key] ?? null);
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

// endregion: ops

// region: terminal ops

/**
 * @template K of array-key
 * @template V
 *
 * @param array<K, V> $dictionary
 * @return list<K>
 * @psalm-return ($dictionary is non-empty-array<K, V> ? non-empty-list<K> : list<K>)
 */
function keys(array $dictionary): array
{
    return array_keys($dictionary);
}

/**
 * @template K of array-key
 * @template V
 *
 * @param array<K, V> $dictionary
 * @return list<V>
 * @psalm-return ($dictionary is non-empty-array<K, V> ? non-empty-list<V> : list<V>)
 */
function values(array $dictionary): array
{
    return array_values($dictionary);
}

/**
 * @return Closure(array<array-key, mixed>): bool
 */
function keyExists(string|int $key): Closure
{
    return fn(array $dictionary) => array_key_exists($key, $dictionary);
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of array<K, A>
 *
 * @param callable(A): Option<B> $callback
 * @return Closure(array<K, A>): Option<array<K, B>>
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? Option<non-empty-array<K, B>>
 *         : Option<array<K, B>>
 * ))
 */
function traverseOption(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $v) {
            $option = $callback($v);

            if (O\isSome($option)) {
                $out[$k] = $option->value;
            } else {
                return O\none;
            }
        }

        return O\some($out);
    };
}

/**
 * @template K of array-key
 * @template A
 *
 * @param array<K, (Closure(): Option<A>) | Option<A>> $dictionary
 * @return Option<array<K, A>>
 * @psalm-return ($dictionary is non-empty-array<K, (Closure(): Option<A>) | Option<A>>
 *     ? Option<non-empty-array<K, A>>
 *     : Option<array<K, A>>)
 */
function sequenceOption(array $dictionary): Option
{
    $out = [];

    foreach ($dictionary as $k => $v) {
        if (!$v instanceof Option) {
            $v = $v();
        }

        if (O\isSome($v)) {
            $out[$k] = $v->value;
        } else {
            return O\none;
        }
    }

    return O\some($out);
}

// endregion: terminal ops
