<?php

declare(strict_types=1);

namespace Fp4\PHP\Str;

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
