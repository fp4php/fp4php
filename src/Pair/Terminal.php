<?php

declare(strict_types=1);

namespace Fp4\PHP\Pair;

/**
 * @template E
 * @template A
 *
 * @param list{E, A} $separated
 * @return E
 */
function left(array $separated): mixed
{
    return $separated[0];
}

/**
 * @template E
 * @template A
 *
 * @param list{E, A} $separated
 * @return A
 */
function right(array $separated): mixed
{
    return $separated[1];
}
