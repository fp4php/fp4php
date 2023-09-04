<?php

declare(strict_types=1);

namespace Fp4\PHP\Pair;

/**
 * @template E
 * @template A
 *
 * @param E $left
 * @param A $right
 * @return list{E, A}
 */
function from(mixed $left, mixed $right): array
{
    return [$left, $right];
}
