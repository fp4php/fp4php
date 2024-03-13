<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Fixture;

abstract class AbstractObj
{
    public function __construct(
        public readonly string $prop1,
    ) {}
}
