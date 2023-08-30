<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Either;

use Fp4\PHP\Type\Either;
use Fp4\PHP\Type\Left;
use Fp4\PHP\Type\Right;
use Throwable;

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

/**
 * @template A
 *
 * @param callable(): A $callback
 * @return Either<Throwable, A>
 */
function tryCatch(callable $callback): Either
{
    try {
        return right($callback());
    } catch (Throwable $e) {
        return left($e);
    }
}
