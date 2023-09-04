<?php

declare(strict_types=1);

namespace Fp4\PHP\Tuple;

use Fp4\PHP\PsalmIntegration\Module\Tuple\FromCallInference;

/**
 * Return type will be widen by {@see FromCallInference} plugin hook.
 *
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
 * Return type will be widen by {@see FromCallInference} plugin hook.
 *
 * @template T of non-empty-list<mixed>
 *
 * @param T $tuple
 * @return T
 */
function fromLiteral(array $tuple): array
{
    return $tuple;
}
