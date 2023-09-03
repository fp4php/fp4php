<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Fixture;

final class InheritedObj extends AbstractObj
{
    public function __construct(
        string $prop1,
        public readonly int $prop2,
    ) {
        parent::__construct($prop1);
    }
}
