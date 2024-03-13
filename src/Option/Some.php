<?php

declare(strict_types=1);

namespace Fp4\PHP\Option;

/**
 * @psalm-immutable
 * @template-covariant A
 * @implements Option<A>
 */
final class Some implements Option
{
    /**
     * @param A $value
     * @internal
     */
    public function __construct(
        public readonly mixed $value,
    ) {}
}
