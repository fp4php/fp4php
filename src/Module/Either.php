<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Either;

use Closure;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Either;
use Fp4\PHP\Type\Left;
use Fp4\PHP\Type\Option;
use Fp4\PHP\Type\Right;

// region: constructor

/**
 * @template E
 *
 * @param E $value
 * @return Either<E, never>
 */
function left(mixed $value): Either
{
    return new Left($value);
}

/**
 * @template A
 *
 * @param A $value
 * @return Either<never, A>
 */
function right(mixed $value): Either
{
    return new Right($value);
}

// endregion: constructor

// region: destructors

/**
 * @template E
 * @template A
 * @param Either<E, A> $e
 * @return Option<E>
 */
function getLeft(Either $e): Option
{
    return isLeft($e) ? O\some($e->value) : O\none;
}

/**
 * @template E
 * @template A
 * @param Either<E, A> $e
 * @return Option<A>
 */
function getRight(Either $e): Option
{
    return isLeft($e) ? O\none : O\some($e->value);
}

/**
 * @template E
 * @template EOut
 * @template A
 * @template AOut
 *
 * @param callable(E): EOut $ifLeft
 * @param callable(A): AOut $ifRight
 * @return Closure(Either<E, A>): (EOut|AOut)
 */
function fold(callable $ifLeft, callable $ifRight): Closure
{
    return fn(Either $e) => isLeft($e)
        ? $ifLeft($e->value)
        : $ifRight($e->value);
}

// endregion: destructors

// region: refinements

/**
 * @template E
 * @template A
 *
 * @param Either<E, A> $e
 * @psalm-assert-if-true Left<E> $e
 */
function isLeft(Either $e): bool
{
    return $e instanceof Left;
}

/**
 * @template E
 * @template A
 *
 * @param Either<E, A> $e
 * @psalm-assert-if-true Right<A> $e
 */
function isRight(Either $e): bool
{
    return $e instanceof Right;
}

// endregion: refinements

// region: ops

/**
 * @template E
 * @template A
 * @template B
 *
 * @param callable(A): B $callback
 * @return Closure(Either<E, A>): Either<E, B>
 */
function map(callable $callback): Closure
{
    return fn(Either $e) => isLeft($e) ? $e : right($callback($e->value));
}

/**
 * @template E
 * @template A
 * @template B
 *
 * @param callable(E): B $callback
 * @return Closure(Either<E, A>): Either<B, A>
 */
function mapLeft(callable $callback): Closure
{
    return fn(Either $e) => isLeft($e) ? left($callback($e->value)) : $e;
}

/**
 * @template E
 * @template A
 * @template B
 *
 * @param callable(A): B $callback
 * @return Closure(Either<E, A>): Either<E, A>
 */
function tap(callable $callback): Closure
{
    return function(Either $e) use ($callback) {
        if (isLeft($e)) {
            return $e;
        }

        $callback($e->value);

        return $e;
    };
}

/**
 * @template E
 * @template A
 * @template B
 *
 * @param callable(E): B $callback
 * @return Closure(Either<E, A>): Either<E, A>
 */
function tapLeft(callable $callback): Closure
{
    return function(Either $e) use ($callback) {
        if (isLeft($e)) {
            $callback($e->value);

            return $e;
        }

        return $e;
    };
}

/**
 * @template EA
 * @template EB
 * @template A
 * @template B
 *
 * @param callable(A): Either<EB, B> $callback
 * @return Closure(Either<EA, A>): Either<EA|EB, B>
 */
function flatMap(callable $callback): Closure
{
    return fn(Either $e) => isLeft($e) ? $e : $callback($e->value);
}

/**
 * @template EA
 * @template EB
 * @template A
 *
 * @param Either<EB, Either<EA, A>> $e
 * @return Either<EB|EA, A>
 */
function flatten(Either $e): Either
{
    return match (true) {
        isLeft($e) => $e,
        isLeft($e->value) => $e->value,
        default => right($e->value->value),
    };
}

/**
 * @template EA
 * @template A
 * @template EB
 * @template B
 *
 * @param Either<EA, A> $fa
 * @return Closure(Either<EB, Closure(A): B>): Either<EA|EB, B>
 */
function ap(Either $fa): Closure
{
    return fn(Either $fab) => match (true) {
        isLeft($fa) => $fa,
        isLeft($fab) => $fab,
        default => right(($fab->value)($fa->value)),
    };
}

/**
 * @template EA
 * @template A
 * @template EB
 * @template B
 *
 * @param callable(): Either<EB, B> $callback
 * @return Closure(Either<EA, A>): Either<EA|EB, A|B>
 */
function orElse(callable $callback): Closure
{
    return fn(Either $e) => match (true) {
        isLeft($e) => $callback(),
        default => $e,
    };
}

/**
 * @template EA
 * @template EB
 * @template A
 *
 * @param callable(A): bool $predicate
 * @param callable(A): EB $else
 * @return Closure(Either<EA, A>): Either<EB|EA, A>
 */
function filterOrElse(callable $predicate, callable $else): Closure
{
    return fn(Either $e) => match (true) {
        isLeft($e) => $e,
        $predicate($e->value) => right($e->value),
        default => left($else($e->value)),
    };
}

/**
 * @template E
 * @template A
 * @param Either<E, A> $e
 * @return Either<A, E>
 */
function swap(Either $e): Either
{
    return match (true) {
        isLeft($e) => right($e->value),
        default => left($e->value),
    };
}

// endregion: ops

// region: bindable

/**
 * @return Either<never, Bindable>
 */
function bindable(): Either
{
    return right(new Bindable());
}

/**
 * @param Closure(Bindable): Either ...$params
 * @return Closure(Either<mixed, Bindable>): Either<mixed, Bindable>
 */
function bind(Closure ...$params): Closure
{
    return function(Either $context) use ($params) {
        if (isLeft($context)) {
            return $context;
        }

        $bindable = $context->value;

        foreach ($params as $key => $param) {
            $result = $param($bindable);

            if (isLeft($result)) {
                return $result;
            }

            $bindable = $bindable->with((string) $key, $result->value);
        }

        return right($bindable);
    };
}

/**
 * @param Closure(Bindable): mixed ...$params
 * @return Closure(Either<mixed, Bindable>): Either<mixed, Bindable>
 */
function let(Closure ...$params): Closure
{
    return function(Either $context) use ($params) {
        if (isLeft($context)) {
            return $context;
        }

        $bindable = $context->value;

        foreach ($params as $key => $param) {
            $bindable = $bindable->with((string) $key, $param($bindable));
        }

        return right($bindable);
    };
}

// endregion: bindable
