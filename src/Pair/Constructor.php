<?php

declare(strict_types=1);

namespace Fp4\PHP\Pair;

use Fp4\PHP\PsalmIntegration\Module\Pair\FromCallInference;

/**
 * Return type will be widen by {@see FromCallInference} plugin hook.
 *
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
