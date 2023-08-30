<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Combinator;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class CombinatorModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            PipeFunctionStorageProvider::class,
            CtorFunctionReturnTypeProvider::class,
        ]);
    }
}
