<?php

declare(strict_types=1);

namespace Fp4\PHP\Option;

use Closure;
use Fp4\PHP\PsalmIntegration\Module\Option\NoneConstInference;
use Fp4\PHP\PsalmIntegration\Module\Option\SomeCallInference;
use Throwable;

/**
 * Return type will be widen by {@see SomeCallInference} plugin hook.
 *
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
 * @internal
 * @return Option<never>
 * @psalm-suppress MixedInferredReturnType, MixedReturnStatement
 */
function none(): Option
{
    return none;
}

/**
 * Return type will be widen by {@see NoneConstInference} plugin hook.
 */
const none = new None();

/**
 * Return type will be widen by {@see SomeCallInference} plugin hook.
 *
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
 * Return type will be widen by {@see SomeCallInference} plugin hook.
 *
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
