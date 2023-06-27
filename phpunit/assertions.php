<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\PHPUnit;

use Closure;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSame;

/**
 * @template T
 * @return Closure(T): T
 */
function equals(mixed $expected, string $message = ''): Closure
{
    return function(mixed $actual) use ($expected, $message): mixed {
        assertEquals($expected, $actual, $message);

        return $actual;
    };
}

/**
 * @template T
 * @return Closure(T): T
 */
function same(mixed $expected, string $message = ''): Closure
{
    return function(mixed $actual) use ($expected, $message): mixed {
        assertSame($expected, $actual, $message);

        return $actual;
    };
}
