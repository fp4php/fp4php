<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\ArrayList;

use Closure;
use Fp4\PHP\Type\Option;
use Fp4\PHP\Module\Option as O;

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
 *
 * @param callable(int, A): B $callback
 * @return Closure(list<A>): list<B>
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
 *
 * @param B $item
 * @return Closure(list<A>): list<A|B>
 */
function prepend(mixed $item): Closure
{
    return fn(array $list) => [$item, ...$list];
}

// endregion: ops

// region: terminal ops

/**
 * @template A
 *
 * @return Closure(list<A>): Option<A>
 */
function last(): Closure
{
    return function(array $list) {
        $lastKey = array_key_last($list) ?? null;

        return null !== $lastKey
            ? O\some($list[$lastKey])
            : O\none();
    };
}

// endregion: terminal ops
