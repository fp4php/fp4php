<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Either;

use Closure;
use Fp4\PHP\Type\Either;

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
        isLeft($fab) => $fab,
        isLeft($fa) => $fa,
        default => right(($fab->value)($fa->value)),
    };
}

/**
 * @template EA
 * @template A
 * @template EB
 * @template B
 *
 * @param callable(EA): Either<EB, B> $callback
 * @return Closure(Either<EA, A>): Either<EA|EB, A|B>
 */
function orElse(callable $callback): Closure
{
    return fn(Either $e) => match (true) {
        isLeft($e) => $callback($e->value),
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
