<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Either;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class EitherModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            LeftRightCallInference::class,
            FilterOrElseCallRefinement::class,
            LetFunctionStorageProvider::class,
            BindFunctionStorageProvider::class,
            BindableCompressor::class,
        ]);
    }
}
