<?php

declare(strict_types=1);

namespace Fp4\PHP\Shape;

/**
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
 * @template T of non-empty-array<array-key, mixed>
 *
 * @param T $shape
 * @return T
 */
function fromLiteral(array $shape): array
{
    return $shape;
}
