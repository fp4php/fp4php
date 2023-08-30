<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Psalm;

use Closure;

/**
 * @template T
 * @param non-empty-string $type
 * @return Closure(T): T
 * @psalm-suppress UnusedParam
 * @codeCoverageIgnore
 */
function isAssignableTo(string $type): Closure
{
    return static fn(mixed $expr): mixed => $expr;
}

/**
 * @template T
 * @param non-empty-string $type
 * @return Closure(T): T
 * @psalm-suppress UnusedParam
 * @codeCoverageIgnore
 */
function isSameAs(string $type): Closure
{
    return static fn(mixed $expr): mixed => $expr;
}
