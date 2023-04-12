<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Bindable;

use PhpParser\Node\Expr;
use Psalm\CodeLocation;
use Psalm\Issue\CodeIssue;
use Psalm\StatementsSource;

final class PropertyIsNotDefinedInScope extends CodeIssue
{
    public static function create(Expr $property, StatementsSource $source): self
    {
        return new self(
            message: 'Property is not defined in the bindable scope.',
            code_location: new CodeLocation($source, $property),
        );
    }
}
