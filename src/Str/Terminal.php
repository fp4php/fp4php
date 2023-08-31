<?php

declare(strict_types=1);

namespace Fp4\PHP\Str;

use Closure;

function isEmpty(string $value): bool
{
    return '' === $value;
}

function length(string $value): int
{
    return mb_strlen($value);
}

/**
 * @param non-empty-string $separator
 * @return Closure(string): non-empty-list<string>
 */
function split(string $separator): Closure
{
    return fn(string $subject) => explode($separator, $subject);
}

/**
 * @return Closure(string): bool
 */
function startsWith(string $needle): Closure
{
    return fn(string $haystack) => str_starts_with($haystack, $needle);
}

/**
 * @return Closure(string): bool
 */
function endsWith(string $needle): Closure
{
    return fn(string $haystack) => str_ends_with($haystack, $needle);
}

/**
 * @return Closure(string): bool
 */
function contains(string $needle): Closure
{
    return fn(string $haystack) => str_contains($haystack, $needle);
}
