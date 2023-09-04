<?php

declare(strict_types=1);

namespace Fp4\PHP\Either;

use Fp4\PHP\PsalmIntegration\Module\Either\LeftRightCallInference;
use Throwable;

/**
 * Return type will be widen by {@see LeftRightCallInference} plugin hook.
 *
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
 * Return type will be widen by {@see LeftRightCallInference} plugin hook.
 *
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
 * Return type will be widen by {@see LeftRightCallInference} plugin hook.
 *
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
