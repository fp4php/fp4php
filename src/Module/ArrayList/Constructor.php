<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\ArrayList;

/**
 * @template A
 *
 * @param iterable<A> $iter
 * @return ($iter is non-empty-array<A> ? non-empty-list<A> : list<A>)
 */
function fromIterable(iterable $iter): array
{
    $list = [];

    foreach ($iter as $a) {
        $list[] = $a;
    }

    return $list;
}

/**
 * @template A
 *
 * @param list<A> $list
 * @return ($list is array<never, never> ? list<never> : list<A>)
 */
function from(array $list): array
{
    return $list;
}

/**
 * @template A
 *
 * @param non-empty-list<A> $list
 * @return non-empty-list<A>
 */
function fromNonEmpty(array $list): array
{
    return $list;
}

/**
 * @template A
 *
 * @param A $value
 * @return non-empty-list<A>
 */
function singleton(mixed $value): array
{
    return [$value];
}
