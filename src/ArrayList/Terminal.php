<?php

declare(strict_types=1);

namespace Fp4\PHP\ArrayList;

use Closure;
use Fp4\PHP\Either as E;
use Fp4\PHP\Either\Either;
use Fp4\PHP\Option as O;
use Fp4\PHP\Option\Option;

use function array_key_exists;
use function in_array;

/**
 * @template A
 *
 * @return Closure(list<A>): Option<A>
 */
function get(int $key): Closure
{
    return fn(array $list) => array_key_exists($key, $list)
        ? O\some($list[$key])
        : O\none;
}

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
 * @return (Closure(TIn): (
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

/**
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(int, A): Option<B> $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? Option<non-empty-list<B>>
 *         : Option<list<B>>
 * ))
 */
function traverseOptionKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $index => $a) {
            $fb = $callback($index, $a);

            if (O\isNone($fb)) {
                return O\none;
            }

            $out[] = $fb->value;
        }

        return O\some($out);
    };
}

/**
 * @template A
 * @template B
 * @template E
 * @template TIn of list<A>
 *
 * @param callable(A): Either<E, B> $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? Either<E, non-empty-list<B>>
 *         : Either<E, list<B>>
 * ))
 */
function traverseEither(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $a) {
            $b = $callback($a);

            if (E\isLeft($b)) {
                return $b;
            }

            $out[] = $b->value;
        }

        return E\right($out);
    };
}

/**
 * @template A
 * @template B
 * @template E
 * @template TIn of list<A>
 *
 * @param callable(int, A): Either<E, B> $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-array<A>
 *         ? Either<E, non-empty-list<B>>
 *         : Either<E, list<B>>
 * ))
 */
function traverseEitherKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $out = [];

        foreach ($list as $index => $a) {
            $b = $callback($index, $a);

            if (E\isLeft($b)) {
                return $b;
            }

            $out[] = $b->value;
        }

        return E\right($out);
    };
}

/**
 * @template A
 *
 * @param callable(A): bool $callback
 * @return Closure(list<A>): bool
 */
function any(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        foreach ($list as $a) {
            if ($callback($a)) {
                return true;
            }
        }

        return false;
    };
}

/**
 * @template A
 *
 * @param callable(int, A): bool $callback
 * @return Closure(list<A>): bool
 */
function anyKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        foreach ($list as $index => $a) {
            if ($callback($index, $a)) {
                return true;
            }
        }

        return false;
    };
}

/**
 * @template A
 *
 * @param callable(A): bool $callback
 * @return Closure(list<A>): bool
 */
function all(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        foreach ($list as $a) {
            if (!$callback($a)) {
                return false;
            }
        }

        return true;
    };
}

/**
 * @template A
 *
 * @param callable(int, A): bool $callback
 * @return Closure(list<A>): bool
 */
function allKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        foreach ($list as $index => $a) {
            if (!$callback($index, $a)) {
                return false;
            }
        }

        return true;
    };
}

/**
 * @template K of array-key
 * @template A
 * @template TIn of list<A>
 *
 * @param callable(A): K $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-list<A>
 *         ? non-empty-array<K, A>
 *         : array<K, A>
 * ))
 */
function reindex(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $dictionary = [];

        foreach ($list as $a) {
            $dictionary[$callback($a)] = $a;
        }

        return $dictionary;
    };
}

/**
 * @template K of array-key
 * @template A
 * @template TIn of list<A>
 *
 * @param callable(int, A): K $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-list<A>
 *         ? non-empty-array<K, A>
 *         : array<K, A>
 * ))
 */
function reindexKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $dictionary = [];

        foreach ($list as $index => $a) {
            $dictionary[$callback($index, $a)] = $a;
        }

        return $dictionary;
    };
}

/**
 * @template A
 *
 * @param callable(A): bool $callback
 * @return Closure(list<A>): array{list<A>, list<A>}
 */
function partition(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $outLeft = [];
        $outRight = [];

        foreach ($list as $item) {
            if (!$callback($item)) {
                $outLeft[] = $item;
            } else {
                $outRight[] = $item;
            }
        }

        return [$outLeft, $outRight];
    };
}

/**
 * @template A
 *
 * @param callable(int, A): bool $callback
 * @return Closure(list<A>): array{list<A>, list<A>}
 */
function partitionKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $outLeft = [];
        $outRight = [];

        foreach ($list as $index => $a) {
            if (!$callback($index, $a)) {
                $outLeft[] = $a;
            } else {
                $outRight[] = $a;
            }
        }

        return [$outLeft, $outRight];
    };
}

/**
 * @template L
 * @template R
 * @template A
 *
 * @param callable(A): Either<L, R> $callback
 * @return Closure(list<A>): array{list<L>, list<R>}
 */
function partitionMap(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $outLeft = [];
        $outRight = [];

        foreach ($list as $item) {
            $either = $callback($item);

            if (E\isLeft($either)) {
                $outLeft[] = $either->value;
            } else {
                $outRight[] = $either->value;
            }
        }

        return [$outLeft, $outRight];
    };
}

/**
 * @template L
 * @template R
 * @template A
 *
 * @param callable(int, A): Either<L, R> $callback
 * @return Closure(list<A>): array{list<L>, list<R>}
 */
function partitionMapKV(callable $callback): Closure
{
    return function(array $list) use ($callback) {
        $outLeft = [];
        $outRight = [];

        foreach ($list as $index => $a) {
            $fa = $callback($index, $a);

            if (E\isLeft($fa)) {
                $outLeft[] = $fa->value;
            } else {
                $outRight[] = $fa->value;
            }
        }

        return [$outLeft, $outRight];
    };
}

/**
 * @template TAcc
 * @template A
 *
 * @param TAcc $initial
 * @param callable(TAcc, A): TAcc $callback
 * @return Closure(list<A>): TAcc
 */
function fold(mixed $initial, callable $callback): Closure
{
    return function(array $list) use ($initial, $callback) {
        $acc = $initial;

        foreach ($list as $a) {
            $acc = $callback($acc, $a);
        }

        return $acc;
    };
}

/**
 * @template TAcc
 * @template A
 *
 * @param TAcc $initial
 * @param callable(TAcc, int, A): TAcc $callback
 * @return Closure(list<A>): TAcc
 */
function foldKV(mixed $initial, callable $callback): Closure
{
    return function(array $list) use ($initial, $callback) {
        $acc = $initial;

        foreach ($list as $index => $a) {
            $acc = $callback($acc, $index, $a);
        }

        return $acc;
    };
}

/**
 * @template K of array-key
 * @template A
 * @template TIn of list<A>
 *
 * @param callable(A): K $callback
 * @return (Closure(TIn): (
 *     TIn is non-empty-list<A>
 *         ? non-empty-array<K, non-empty-list<A>>
 *         : array<K, non-empty-list<A>>
 * ))
 */
function group(callable $callback): Closure
{
    return function(array $fa) use ($callback) {
        $fb = [];

        foreach ($fa as $a) {
            $kb = $callback($a);

            if (!isset($fb[$kb])) {
                $fb[$kb] = [];
            }

            $fb[$kb][] = $a;
        }

        return $fb;
    };
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(A): K $group
 * @param callable(A): B $map
 * @return (Closure(TIn): (
 *     TIn is non-empty-list<A>
 *         ? non-empty-array<K, non-empty-list<B>>
 *         : array<K, non-empty-list<B>>
 * ))
 */
function groupMap(callable $group, callable $map): Closure
{
    return function(array $fa) use ($group, $map) {
        $fb = [];

        foreach ($fa as $a) {
            $kb = $group($a);

            if (!isset($fb[$kb])) {
                $fb[$kb] = [];
            }

            $fb[$kb][] = $map($a);
        }

        return $fb;
    };
}

/**
 * @template K of array-key
 * @template A
 * @template B
 * @template TIn of list<A>
 *
 * @param callable(A): K $group
 * @param callable(A): B $map
 * @param callable(B, B): B $reduce
 * @return (Closure(TIn): (TIn is non-empty-list<A> ? non-empty-array<K, B> : array<K, B>))
 */
function groupMapReduce(callable $group, callable $map, callable $reduce): Closure
{
    return function(array $fa) use ($group, $map, $reduce) {
        $fb = [];

        foreach ($fa as $a) {
            $kb = $group($a);
            $b = $map($a);

            $fb[$kb] = isset($fb[$kb]) ? $reduce($b, $fb[$kb]) : $b;
        }

        return $fb;
    };
}
