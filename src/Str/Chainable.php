<?php

declare(strict_types=1);

namespace Fp4\PHP\Str;

use Closure;

use function trim as nativeTrim;

/**
 * @template TIn of string
 * @template TSuffix of string
 *
 * @param TSuffix $suffix
 * @return (Closure(TIn): (
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
 * @return (Closure(TIn): (
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
