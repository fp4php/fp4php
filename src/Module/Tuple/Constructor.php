<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Tuple;

/**
 * @template T of non-empty-list<mixed>
 *
 * @param T $tuple
 * @return T
 */
function from(array $tuple): array
{
    return $tuple;
}

/**
 * @template T of non-empty-list<mixed>
 *
 * @param T $tuple
 * @return T
 */
function fromLiteral(array $tuple): array
{
    return $tuple;
}
