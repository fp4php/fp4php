<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Str;

use Closure;

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
