<?php

declare(strict_types=1);

namespace Fp4\PHP\Either;

/**
 * @template-covariant E
 * @template-covariant A
 * @psalm-inheritors Left<E>|Right<A>
 */
interface Either
{
}
