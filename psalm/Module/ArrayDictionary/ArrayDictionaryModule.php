<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayDictionary;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class ArrayDictionaryModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            FilterCallRefinement::class,
            PartitionCallRefinement::class,
            FromCallInference::class,
            FoldInference::class,
            PropertyInference::class,
        ]);
    }
}
