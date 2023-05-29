<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Psalm;

/**
 * @template T
 * @param T $expr
 * @return T
 */
function dumpType(mixed $expr): mixed
{
    return $expr;
}

/**
 * @template T
 * @param T $expr
 * @return T
 *
 * @psalm-suppress UnusedParam
 */
function assertType(mixed $expr, string $type): mixed
{
    return $expr;
}

/**
 * @template T
 * @param T $expr
 * @return T
 *
 * @psalm-suppress UnusedParam
 */
function suppressIssue(mixed $expr, string $issue, string $message): mixed
{
    return $expr;
}
