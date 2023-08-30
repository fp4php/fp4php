<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Combinator;

use Closure;

/**
 * @no-named-arguments
 */
function pipe(mixed $a, callable $head, callable ...$tail): mixed
{
    foreach ([$head, ...$tail] as $function) {
        $a = $function($a);
    }

    return $a;
}

/**
 * @template A
 *
 * @param class-string<A> $ofClass
 * @return Closure(mixed...): A
 */
function ctor(string $ofClass): Closure
{
    /** @psalm-suppress MixedMethodCall */
    return fn(mixed ...$args) => new $ofClass(...$args);
}

/**
 * @template A
 * @param A $value
 * @return A
 */
function id(mixed $value): mixed
{
    return $value;
}

/**
 * @psalm-return null
 */
function constNull(): mixed
{
    return null;
}

function constVoid(): void
{
}

function constTrue(): bool
{
    return true;
}

function constFalse(): bool
{
    return false;
}
