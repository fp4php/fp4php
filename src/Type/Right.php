<?php

declare(strict_types=1);

namespace Fp4\PHP\Type;

/**
 * @template-covariant A
 * @implements Either<never, A>
 */
final class Right implements Either
{
    /**
     * @param A $value
     */
    public function __construct(
        public readonly mixed $value,
    ) {
    }
}
