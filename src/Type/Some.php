<?php

declare(strict_types=1);

namespace Fp4\PHP\Type;

/**
 * @phpstan-immutable
 * @template-covariant A
 * @extends Option<A>
 */
final class Some extends Option
{
    /**
     * @param A $value
     * @internal
     */
    public function __construct(
        public readonly mixed $value,
    ) {
    }
}
