<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Create\CreateType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Types;
use Psalm\Codebase;

final class PsalmApi
{
    public static Types $types;
    public static CreateType $create;
    public static Codebase $codebase;
}
