<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Functions;

/**
 * @no-named-arguments
 */
function pipe(mixed $a, callable $head, callable ...$tail): mixed
{
    foreach ([$head, ...$tail] as $function) {
        /** @psalm-suppress MixedAssignment */
        $a = $function($a);
    }

    return $a;
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
