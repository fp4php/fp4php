<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Str;

use Closure;

/**
 * @template TIn of string
 * @psalm-return (Closure(TIn): (
 *     TIn is non-empty-string
 *         ? non-empty-string
 *         : string
 * ))
 */
function prepend(string $prefix): Closure
{
    return fn(string $value) => "{$prefix}{$value}";
}
