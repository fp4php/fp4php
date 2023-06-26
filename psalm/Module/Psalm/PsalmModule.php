<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Psalm;

use Fp4\PHP\PsalmIntegration\RegisterPsalmHooks;

final class PsalmModule implements RegisterPsalmHooks
{
    public function __invoke(callable $register): void
    {
        $register([
            DumpTypeHandler::class,
            ExpectTypeHandler::class,
            SuppressIssueHandler::class,
        ]);
    }
}
