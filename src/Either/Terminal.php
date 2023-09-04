<?php

declare(strict_types=1);

namespace Fp4\PHP\Either;

use Fp4\PHP\Option as O;

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

/**
 * @template E
 * @template A
 *
 * @param Either<E, A> $e
 * @return E|A
 */
function unwrap(Either $e): mixed
{
    /** @var Left<E>|Right<A> $e */;

    return $e->value;
}

/**
 * @template E
 * @template A
 *
 * @param Either<E, A> $e
 * @return O\Option<E>
 */
function getLeft(Either $e): O\Option
{
    return isLeft($e) ? O\some($e->value) : O\none;
}

/**
 * @template E
 * @template A
 *
 * @param Either<E, A> $e
 * @return O\Option<A>
 */
function getRight(Either $e): O\Option
{
    return isLeft($e) ? O\none : O\some($e->value);
}
