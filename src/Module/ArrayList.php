<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\ArrayList;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;

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
 * @return Closure(list<A>): Option<A>
 */
function first(): Closure
{
    return function (array $list) {
        $firstKey = array_key_first($list) ?? null;

        return null !== $firstKey
            ? O\some($list[$firstKey])
            : O\none;
    };
}

/**
 * @template A
 *
 * @return Closure(list<A>): Option<A>
 */
function last(): Closure
{
    return function (array $list) {
        $lastKey = array_key_last($list) ?? null;

        return null !== $lastKey
            ? O\some($list[$lastKey])
            : O\none;
    };
}

// endregion: terminal ops
