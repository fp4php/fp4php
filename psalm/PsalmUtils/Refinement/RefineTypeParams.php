<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement;

use Psalm\Type\Union;

final class RefineTypeParams
{
    public function __construct(
        public readonly Union $key,
        public readonly Union $value,
    ) {
    }
}
