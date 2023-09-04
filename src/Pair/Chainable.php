<?php

declare(strict_types=1);

namespace Fp4\PHP\Pair;

use Closure;

/**
 * @template E
 * @template A
 * @template B
 *
 * @param callable(A): B $callback
 * @return Closure(list{E, A}): list{E, B}
 */
function map(callable $callback): Closure
{
    return fn(array $separated) => [$separated[0], $callback($separated[1])];
}

/**
 * @template E
 * @template A
 * @template B
 *
 * @param callable(E): B $callback
 * @return Closure(list{E, A}): list{B, A}
 */
function mapLeft(callable $callback): Closure
{
    return fn(array $separated) => [$callback($separated[0]), $separated[1]];
}
