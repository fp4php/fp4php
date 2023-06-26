<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration;

interface RegisterPsalmHooks
{
    /**
     * @param callable(non-empty-list<string>): void $register
     */
    public function __invoke(callable $register): void;
}
