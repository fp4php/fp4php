<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Shape;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class ShapeModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            FromCallInference::class,
        ]);
    }
}
