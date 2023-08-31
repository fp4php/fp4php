<?php

declare(strict_types=1);

namespace Fp4\PHP\Option;

use Closure;
use Throwable;

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

/**
 * @return Option<never>
 * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
 */
function none(): Option
{
    return none;
}

const none = new None();

/**
 * @template A
 *
 * @param A|null $value
 * @return ($value is null ? Option<never> : Option<A>)
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

/**
 * @template A
 *
 * @param Closure(): Option<A> $head
 * @param Closure(): Option<A> ...$tail
 * @return Option<A>
 */
function first(Closure $head, Closure ...$tail): Option
{
    foreach ([$head, ...$tail] as $option) {
        $o = $option();

        if (isSome($o)) {
            return $o;
        }
    }

    return none;
}
