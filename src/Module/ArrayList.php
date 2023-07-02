<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\ArrayList;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;

use function array_key_exists;
use function in_array;

// region: constructor

/**
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
 * @template A
 *
 * @param list<A> $list
 * @return ($list is array<never, never> ? list<never> : list<A>)
 */
function from(array $list): array
{
    return $list;
}

/**
 * @template A
 *
 * @param non-empty-list<A> $list
 * @return non-empty-list<A>
 */
function fromNonEmpty(array $list): array
{
    return $list;
}

// endregion: constructor

// region: ops

/**
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
 * @template K of array-key
 * @template A
 * @template TIn of list<A>
 *
 * @param callable(A): K $callback
 * @return Closure(list<A>): array<K, A>
 * @psalm-return (Closure(TIn): (
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

// endregion: terminal ops

// region: bindable

/**
 * @return list<Bindable>
 */
function bindable(): array
{
    return [new Bindable()];
}

/**
 * @param Closure(Bindable): list<mixed> ...$params
 * @return Closure(list<Bindable>): list<Bindable>
 */
function bind(Closure ...$params): Closure
{
    return function(array $list) use ($params) {
        foreach ($params as $key => $param) {
            $cartesian = [];

            foreach ($list as $bindable) {
                foreach ($param($bindable) as $item) {
                    $cartesian[] = $bindable->with((string) $key, $item);
                }
            }

            $list = $cartesian;
        }

        return $list;
    };
}

/**
 * @param Closure(Bindable): mixed ...$params
 * @return Closure(list<Bindable>): list<Bindable>
 */
function let(Closure ...$params): Closure
{
    return function(array $list) use ($params) {
        foreach ($params as $key => $param) {
            $cartesian = [];

            foreach ($list as $bindable) {
                $cartesian[] = $bindable->with((string) $key, $param($bindable));
            }

            $list = $cartesian;
        }

        return $list;
    };
}

// endregion: bindable
