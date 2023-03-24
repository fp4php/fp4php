<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Option;

use Closure;
use Fp4\PHP\Type\None;
use Fp4\PHP\Type\Option;
use Fp4\PHP\Type\Some;

// region: constructor

/**
 * @template A
 *
 * @param A $value
 * @return Option<A>
 *
 * @phpstan-pure
 */
function some(mixed $value): Option
{
    return new Some($value);
}

/**
 * @return Option<never>
 *
 * @phpstan-pure
 */
function none(): Option
{
    return new None();
}

// endregion: constructor

// region: destructors

/**
 * @template A
 *
 * @param Option<A> $option
 * @return (A|null)
 *
 * @phpstan-pure
 */
function get(Option $option): mixed
{
    return isSome($option)
        ? $option->value
        : null;
}

/**
 * @template A
 * @template TNone
 * @template TSome
 *
 * @param callable(): TNone $ifNone
 * @param callable(A): TSome $ifSome
 * @return Closure(Option<A>): (TNone|TSome)
 */
function fold(callable $ifNone, callable $ifSome): Closure
{
    return fn(Option $option) => isSome($option)
        ? $ifSome($option->value)
        : $ifNone();
}

// endregion: destructors

// region: refinements

/**
 * @template A
 *
 * @param Option<A> $option
 * @phpstan-assert-if-true Some<A> $option
 * @phpstan-assert-if-false None $option
 *
 * @phpstan-pure
 */
function isSome(Option $option): bool
{
    return $option instanceof Some;
}

/**
 * @template A
 *
 * @param Option<A> $option
 * @phpstan-assert-if-true None $option
 * @phpstan-assert-if-false Some<A> $option
 *
 * @phpstan-pure
 */
function isNone(Option $option): bool
{
    return $option instanceof None;
}

// endregion: refinements
