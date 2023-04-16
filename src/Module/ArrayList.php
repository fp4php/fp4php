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
 * @template A
 *
 * @param iterable<A> $iterable
 * @return list<A>
 * @psalm-return ($iterable is non-empty-array<A> ? non-empty-list<A> : list<A>)
 */
function fromIterable(iterable $iterable): array
{
    $list = [];

    foreach ($iterable as $item) {
        $list[] = $item;
    }

    return $list;
}

/**
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
 * @template A
 * @template B
 * @template TIn of array<A>
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
    return function(array $a) use ($callback) {
        $b = [];

        foreach ($a as $v) {
            $b[] = $callback($v);
        }

        return $b;
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
    return function(array $a) use ($callback) {
        $b = [];

        foreach ($a as $k => $v) {
            $b[] = $callback($k, $v);
        }

        return $b;
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
    return function(array $a) use ($callback) {
        $b = [];

        foreach ($a as $v) {
            foreach ($callback($v) as $nested) {
                $b[] = $nested;
            }
        }

        return $b;
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
    return function(array $a) use ($callback) {
        $b = [];

        foreach ($a as $k => $v) {
            foreach ($callback($k, $v) as $nested) {
                $b[] = $nested;
            }
        }

        return $b;
    };
}

/**
 * @template A
 * @template B
 *
 * @param B $item
 * @return Closure(list<A>): non-empty-list<A|B>
 */
function prepend(mixed $item): Closure
{
    return fn(array $list) => [$item, ...$list];
}

/**
 * @template A
 * @template B
 *
 * @param B $item
 * @return Closure(list<A>): non-empty-list<A|B>
 */
function append(mixed $item): Closure
{
    return fn(array $list) => [...$list, $item];
}

// endregion: ops

// region: terminal ops

/**
 * @return Closure(list<mixed>): bool
 */
function contains(mixed $item): Closure
{
    return fn(array $list) => in_array($item, $list, strict: true);
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

        foreach ($list as $item) {
            $option = $callback($item);

            if (O\isSome($option)) {
                $out[] = $option->value;
            } else {
                return O\none;
            }
        }

        return O\some($out);
    };
}

// endregion: terminal ops
