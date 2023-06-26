<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Option;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class OptionModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            FilterCallRefinement::class,
            NoneConstInference::class,
            SomeCallInference::class,
        ]);
    }
}
