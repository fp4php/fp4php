<?php

declare(strict_types=1);

namespace Fp4\PHP\ArrayDictionary;

use Fp4\PHP\PsalmIntegration\Module\ArrayDictionary\FromCallInference;

/**
 * Return type will be widen by {@see FromCallInference} plugin hook.
 *
 * @template K of array-key
 * @template A
 *
 * @param iterable<K, A> $iterable
 * @return ($iterable is non-empty-array<K, A> ? non-empty-array<K, A> : array<K, A>)
 */
function fromIterable(iterable $iterable): array
{
    $dictionary = [];

    foreach ($iterable as $k => $v) {
        $dictionary[$k] = $v;
    }

    return $dictionary;
}

/**
 * Return type will be widen by {@see FromCallInference} plugin hook.
 *
 * @template K of array-key
 * @template A
 *
 * @param array<K, A> $dictionary
 * @return array<K, A>
 */
function from(array $dictionary): array
{
    return $dictionary;
}

/**
 * Return type will be widen by {@see FromCallInference} plugin hook.
 *
 * @template K of array-key
 * @template A
 *
 * @param non-empty-array<K, A> $dictionary
 * @return non-empty-array<K, A>
 */
function fromNonEmpty(array $dictionary): array
{
    return $dictionary;
}
