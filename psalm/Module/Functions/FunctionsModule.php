<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Functions;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class FunctionsModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            PipeFunctionStorageProvider::class,
        ]);
    }
}
