<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Option;

use Closure;
use Fp4\PHP\Type\None;
use Fp4\PHP\Type\Option;
use Fp4\PHP\Type\Some;

/**
 * @template A
 *
 * @param Option<A> $option
 * @psalm-assert-if-true Some<A> $option
 */
function isSome(Option $option): bool
{
    return $option instanceof Some;
}

/**
 * @template A
 *
 * @param Option<A> $option
 * @psalm-assert-if-true None $option
 */
function isNone(Option $option): bool
{
    return $option instanceof None;
}

/**
 * @template A
 *
 * @param Option<A> $option
 * @return A|null
 */
function getOrNull(Option $option): mixed
{
    return isSome($option)
        ? $option->value
        : null;
}

/**
 * @template A
 * @template B
 *
 * @param B $value
 * @return Closure(Option<A>): (A|B)
 */
function getOrElse(mixed $value): Closure
{
    return fn(Option $option) => isSome($option)
        ? $option->value
        : $value;
}

/**
 * @template A
 * @template B
 *
 * @param callable(): B $call
 * @return Closure(Option<A>): (A|B)
 */
function getOrCall(callable $call): Closure
{
    return fn(Option $option) => isSome($option)
        ? $option->value
        : $call();
}
