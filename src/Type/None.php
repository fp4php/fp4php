<?php

declare(strict_types=1);

namespace Fp4\PHP\Type;

/**
 * @phpstan-immutable
 * @extends Option<never>
 */
final class None extends Option
{
    /**
     * @internal
     */
    public function __construct()
    {
    }
}
