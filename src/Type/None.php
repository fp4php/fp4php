<?php

declare(strict_types=1);

namespace Fp4\PHP\Type;

/**
 * @psalm-immutable
 * @implements Option<never>
 */
final class None implements Option
{
    /**
     * @internal
     */
    public function __construct()
    {
    }
}
