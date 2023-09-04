<?php

declare(strict_types=1);

namespace Fp4\PHP\ArrayDictionary;

use Closure;
use Fp4\PHP\Either as E;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\Module\ArrayDictionary\PartitionCallRefinement;
use Fp4\PHP\PsalmIntegration\Module\ArrayList\FoldInference;

use function array_key_exists;

/**
 * @template K of array-key
 * @template A
 *
 * @return Closure(array<K, A>): O\Option<A>
 */
function get(int|string $key): Closure
{
    return fn(array $dictionary) => O\fromNullable($dictionary[$key] ?? null);
}

/**
 * @template K of array-key
 * @template V
 *
 * @param array<K, V> $dictionary
 * @return ($dictionary is non-empty-array<K, V> ? non-empty-list<K> : list<K>)
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
 * @return ($dictionary is non-empty-array<K, V> ? non-empty-list<V> : list<V>)
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
 * @param callable(A): O\Option<B> $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? O\Option<non-empty-array<K, B>>
 *         : O\Option<array<K, B>>
 * ))
 */
function traverseOption(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $v) {
            $option = $callback($v);

            if (O\isNone($option)) {
                return O\none;
            }

            $out[$k] = $option->value;
        }

        return O\some($out);
    };
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of array<K, A>
 *
 * @param callable(K, A): O\Option<B> $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? O\Option<non-empty-array<K, B>>
 *         : O\Option<array<K, B>>
 * ))
 */
function traverseOptionKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $v) {
            $option = $callback($k, $v);

            if (O\isNone($option)) {
                return O\none;
            }

            $out[$k] = $option->value;
        }

        return O\some($out);
    };
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template E
 * @template TIn of array<K, A>
 *
 * @param callable(A): E\Either<E, B> $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? E\Either<E, non-empty-array<K, B>>
 *         : E\Either<E, array<K, B>>
 * ))
 */
function traverseEither(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $a) {
            $fb = $callback($a);

            if (E\isLeft($fb)) {
                return $fb;
            }

            $out[$k] = $fb->value;
        }

        return E\right($out);
    };
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template E
 * @template TIn of array<K, A>
 *
 * @param callable(K, A): E\Either<E, B> $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<K, A>
 *         ? E\Either<E, non-empty-array<K, B>>
 *         : E\Either<E, array<K, B>>
 * ))
 */
function traverseEitherKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $out = [];

        foreach ($dictionary as $k => $a) {
            $fb = $callback($k, $a);

            if (E\isLeft($fb)) {
                return $fb;
            }

            $out[$k] = $fb->value;
        }

        return E\right($out);
    };
}

/**
 * @template K of array-key
 * @template V
 *
 * @param callable(V): bool $callback
 * @return Closure(array<K, V>): bool
 */
function any(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        foreach ($dictionary as $v) {
            if ($callback($v)) {
                return true;
            }
        }

        return false;
    };
}

/**
 * @template K of array-key
 * @template V
 *
 * @param callable(K, V): bool $callback
 * @return Closure(array<K, V>): bool
 */
function anyKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        foreach ($dictionary as $k => $v) {
            if ($callback($k, $v)) {
                return true;
            }
        }

        return false;
    };
}

/**
 * @template K of array-key
 * @template V
 *
 * @param callable(V): bool $callback
 * @return Closure(array<K, V>): bool
 */
function all(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        foreach ($dictionary as $v) {
            if (!$callback($v)) {
                return false;
            }
        }

        return true;
    };
}

/**
 * @template K of array-key
 * @template V
 *
 * @param callable(K, V): bool $callback
 * @return Closure(array<K, V>): bool
 */
function allKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        foreach ($dictionary as $k => $v) {
            if (!$callback($k, $v)) {
                return false;
            }
        }

        return true;
    };
}

/**
 * Function call will be inferred by {@see PartitionCallRefinement}.
 *
 * @template K of array-key
 * @template V
 *
 * @param callable(V): bool $callback
 * @return Closure(array<K, V>): list{array<K, V>, array<K, V>}
 */
function partition(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $outLeft = [];
        $outRight = [];

        foreach ($dictionary as $k => $v) {
            if (!$callback($v)) {
                $outLeft[$k] = $v;
            } else {
                $outRight[$k] = $v;
            }
        }

        return [$outLeft, $outRight];
    };
}

/**
 * Function call will be inferred by {@see PartitionCallRefinement}.
 *
 * @template K of array-key
 * @template V
 *
 * @param callable(K, V): bool $callback
 * @return Closure(array<K, V>): list{array<K, V>, array<K, V>}
 */
function partitionKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $outLeft = [];
        $outRight = [];

        foreach ($dictionary as $k => $v) {
            if (!$callback($k, $v)) {
                $outLeft[$k] = $v;
            } else {
                $outRight[$k] = $v;
            }
        }

        return [$outLeft, $outRight];
    };
}

/**
 * @template L
 * @template R
 * @template K of array-key
 * @template V
 *
 * @param callable(V): E\Either<L, R> $callback
 * @return Closure(array<K, V>): list{array<K, L>, array<K, R>}
 */
function partitionMap(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $outLeft = [];
        $outRight = [];

        foreach ($dictionary as $k => $v) {
            $either = $callback($v);

            if (E\isLeft($either)) {
                $outLeft[$k] = $either->value;
            } else {
                $outRight[$k] = $either->value;
            }
        }

        return [$outLeft, $outRight];
    };
}

/**
 * @template L
 * @template R
 * @template K of array-key
 * @template V
 *
 * @param callable(K, V): E\Either<L, R> $callback
 * @return Closure(array<K, V>): list{array<K, L>, array<K, R>}
 */
function partitionMapKV(callable $callback): Closure
{
    return function(array $dictionary) use ($callback) {
        $outLeft = [];
        $outRight = [];

        foreach ($dictionary as $k => $v) {
            $either = $callback($k, $v);

            if (E\isLeft($either)) {
                $outLeft[$k] = $either->value;
            } else {
                $outRight[$k] = $either->value;
            }
        }

        return [$outLeft, $outRight];
    };
}

/**
 * Function call will be inferred by {@see FoldInference}.
 *
 * @template TAcc
 * @template K of array-key
 * @template V
 *
 * @param TAcc $initial
 * @param callable(TAcc, V): TAcc $callback
 * @return Closure(array<K, V>): TAcc
 */
function fold(mixed $initial, callable $callback): Closure
{
    return function(array $dictionary) use ($initial, $callback) {
        $acc = $initial;

        foreach ($dictionary as $_k => $v) {
            $acc = $callback($acc, $v);
        }

        return $acc;
    };
}

/**
 * Function call will be inferred by {@see FoldInference}.
 *
 * @template TAcc
 * @template K of array-key
 * @template V
 *
 * @param TAcc $initial
 * @param callable(TAcc, K, V): TAcc $callback
 * @return Closure(array<K, V>): TAcc
 */
function foldKV(mixed $initial, callable $callback): Closure
{
    return function(array $dictionary) use ($initial, $callback) {
        $acc = $initial;

        foreach ($dictionary as $k => $v) {
            $acc = $callback($acc, $k, $v);
        }

        return $acc;
    };
}
