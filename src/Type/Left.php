<?php

declare(strict_types=1);

namespace Fp4\PHP\Type;

/**
 * @template-covariant E
 * @implements Either<E, never>
 */
final class Left implements Either
{
    /**
     * @param E $value
     */
    public function __construct(
        public readonly mixed $value,
    ) {
    }
}
