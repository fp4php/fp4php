<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

final class AsNonLiteralTypeConfig
{
    public function __construct(
        public readonly bool $preserveKeyedArrayShape = false,
        public readonly bool $transformNested = true,
    ) {
    }

    public function stopTransformNested(): self
    {
        return new self($this->preserveKeyedArrayShape, transformNested: false);
    }
}
