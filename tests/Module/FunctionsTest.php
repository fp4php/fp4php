<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

final class FunctionsTest extends TestCase
{
    #[Test]
    public static function pipe(): void
    {
        assertEquals(42, pipe(
            40,
            fn(int $i) => $i - 1,
            fn(int $i) => $i + 3,
        ));
    }

    #[Test]
    public static function constNull(): void
    {
        assertNull(constNull());
    }
}
