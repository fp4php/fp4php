<?php

declare(strict_types=1);

namespace Fp4\PHP\Shape;

use Fp4\PHP\PsalmIntegration\Module\Shape\FromCallInference;

/**
 * Return type will be widen by {@see FromCallInference} plugin hook.
 *
 * @template T of non-empty-array<array-key, mixed>
 *
 * @param T $shape
 * @return T
 */
function from(array $shape): array
{
    return $shape;
}

/**
 * Return type will be widen by {@see FromCallInference} plugin hook.
 *
 * @template T of non-empty-array<array-key, mixed>
 *
 * @param T $shape
 * @return T
 */
function fromLiteral(array $shape): array
{
    return $shape;
}
