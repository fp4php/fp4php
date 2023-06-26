<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Bindable;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class BindableModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            LetFunctionStorageProvider::class,
            BindFunctionStorageProvider::class,
            BindablePropertiesResolver::class,
            BindableGetReturnTypeProvider::class,
        ]);
    }
}
