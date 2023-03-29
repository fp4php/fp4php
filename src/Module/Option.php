<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Option;

use Closure;
use Fp4\PHP\Type\None;
use Fp4\PHP\Type\Option;
use Fp4\PHP\Type\Some;
use Throwable;

// region: constructor

/**
 * @template A
 *
 * @param A $value
 * @return Option<A>
 */
function some(mixed $value): Option
{
    return new Some($value);
}

const none = new None();

/**
 * @template A
 *
 * @param A|null $value
 * @return Option<A>
 */
function fromNullable(mixed $value): Option
{
    return null !== $value ? some($value) : none;
}

/**
 * @template A
 *
 * @param callable(): A $callback
 * @return Option<A>
 */
function tryCatch(callable $callback): Option
{
    try {
        return some($callback());
    } catch (Throwable) {
        return none;
    }
}

// endregion: constructor

// region: destructors

/**
 * @template A
 *
 * @return Closure(Option<A>): (A|null)
 */
function getOrNull(): Closure
{
    return fn (Option $option) => isSome($option)
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
    return fn (Option $option) => isSome($option)
        ? $ifSome($option->value)
        : $ifNone();
}

// endregion: destructors

// region: refinements

/**
 * @template A
 *
 * @param Option<A> $option
 * @psalm-assert-if-true Some<A> $option
 * @psalm-assert-if-false None $option
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
 * @psalm-assert-if-false Some<A> $option
 */
function isNone(Option $option): bool
{
    return $option instanceof None;
}

// endregion: refinements

// region: ops

/**
 * @template A
 * @template B
 *
 * @param callable(A): B $callback
 * @return Closure(Option<A>): Option<B>
 */
function map(callable $callback): Closure
{
    return fn (Option $option) => isSome($option)
        ? some($callback($option->value))
        : none;
}

/**
 * @template A
 * @template B
 *
 * @param callable(A): Option<B> $callback
 * @return Closure(Option<A>): Option<B>
 */
function flatMap(callable $callback): Closure
{
    return fn (Option $option) => isSome($option)
        ? $callback($option->value)
        : none;
}

/**
 * @template A
 * @template B
 *
 * @param callable(): Option<B> $callback
 * @return Closure(Option<A>): Option<A|B>
 */
function orElse(callable $callback): Closure
{
    return fn (Option $option) => isSome($option)
        ? some($option->value)
        : $callback();
}

// endregion: ops
