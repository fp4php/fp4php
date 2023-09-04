<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Combinator;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class CombinatorModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            PipeErrorLocator::class,
            PipeFunctionStorageProvider::class,
            TupledFunctionStorageProvider::class,
            TupledReturnTypeProvider::class,
            CtorFunctionReturnTypeProvider::class,
        ]);
    }
}
