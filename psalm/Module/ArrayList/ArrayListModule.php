<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class ArrayListModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            FromCallInference::class,
            FilterCallRefinement::class,
            PartitionCallRefinement::class,
            LetFunctionStorageProvider::class,
            BindFunctionStorageProvider::class,
            BindableCompressor::class,
            FoldInference::class,
            PropertyInference::class,
        ]);
    }
}
