<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Str;

use Closure;

use function trim as nativeTrim;

function from(string $string): string
{
    return $string;
}

/**
 * @param non-empty-string $string
 * @return non-empty-string
 */
function fromNonEmpty(string $string): string
{
    return $string;
}

/**
 * @template TIn of string
 * @template TSuffix of string
 *
 * @param TSuffix $suffix
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-string ? non-empty-string :
 *     TSuffix is non-empty-string ? non-empty-string :
 *     string
 * ))
 */
function append(string $suffix): Closure
{
    return fn(string $value) => "{$value}{$suffix}";
}

/**
 * @template TIn of string
 * @template TPrefix of string
 *
 * @param TPrefix $prefix
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-string ? non-empty-string :
 *     TPrefix is non-empty-string ? non-empty-string :
 *     string
 * ))
 */
function prepend(string $prefix): Closure
{
    return fn(string $value) => "{$prefix}{$value}";
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

function isEmpty(string $value): bool
{
    return '' === $value;
}

function length(string $value): int
{
    return mb_strlen($value);
}

/**
 * @return Closure(string): string
 */
function replace(string $search, string $replace): Closure
{
    return fn(string $subject) => str_replace($search, $replace, $subject);
}

/**
 * @return Closure(string): string
 */
function substring(int $start, int $length): Closure
{
    return fn(string $subject) => mb_substr($subject, $start, $length);
}

/**
 * @param non-empty-string $separator
 * @return Closure(string): non-empty-list<string>
 */
function split(string $separator): Closure
{
    return fn(string $subject) => explode($separator, $subject);
}

function toLowerCase(string $subject): string
{
    return strtolower($subject);
}

function toUpperCase(string $subject): string
{
    return strtoupper($subject);
}

function trim(string $subject): string
{
    return nativeTrim($subject);
}

function trimLeft(string $subject): string
{
    return ltrim($subject);
}

function trimRight(string $subject): string
{
    return rtrim($subject);
}
