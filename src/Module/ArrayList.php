<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\ArrayList;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;

// region: constructor

/**
 * @template A
 *
 * @param iterable<A> $iterable
 * @return list<A>
 */
function fromIterable(iterable $iterable): array
{
    $list = [];

    foreach ($iterable as $item) {
        $list[] = $item;
    }

    return $list;
}

// endregion: constructor

// region: ops

/**
 * @template A
 * @template B
 *
 * @param callable(A): B $callback
 * @return Closure(list<A>): list<B>
 */
function map(callable $callback): Closure
{
    return function (array $a) use ($callback) {
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
 *
 * @param callable(int, A): B $callback
 * @return Closure(list<A>): list<B>
 */
function mapKV(callable $callback): Closure
{
    return function (array $a) use ($callback) {
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
 *
 * @param callable(A): list<B> $callback
 * @return Closure(list<A>): list<B>
 */
function flatMap(callable $callback): Closure
{
    return function (array $a) use ($callback) {
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
 *
 * @param callable(int, A): list<B> $callback
 * @return Closure(list<A>): list<B>
 */
function flatMapKV(callable $callback): Closure
{
    return function (array $a) use ($callback) {
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
 * @return Closure(list<A>): list<A|B>
 */
function prepend(mixed $item): Closure
{
    return fn (array $list) => [$item, ...$list];
}

/**
 * @template A
 * @template B
 *
 * @param B $item
 * @return Closure(list<A>): list<A|B>
 */
function append(mixed $item): Closure
{
    return fn (array $list) => [...$list, $item];
}

// endregion: ops

// region: terminal ops

/**
 * @template A
 *
 * @param list<A> $list
 * @return Option<A>
 */
function first(array $list): Option
{
    $firstKey = array_key_first($list) ?? null;

    return null !== $firstKey
        ? O\some($list[$firstKey])
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
 *
 * @param callable(A): Option<B> $callback
 * @return Closure(list<A>): Option<list<B>>
 */
function traverseOption(callable $callback): Closure
{
    return function (array $list) use ($callback) {
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

/**
 * @template A
 *
 * @param list<(Closure(): Option<A>) | Option<A>> $list
 * @return Option<list<A>>
 */
function sequenceOption(array $list): Option
{
    $out = [];

    foreach ($list as $option) {
        if (!$option instanceof Option) {
            $option = $option();
        }

        if (O\isSome($option)) {
            $out[] = $option->value;
        } else {
            return O\none;
        }
    }

    return O\some($out);
}

// endregion: terminal ops
