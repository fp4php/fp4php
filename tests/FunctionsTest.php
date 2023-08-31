<?php

declare(strict_types=1);

namespace Fp4\PHP\Test;

use DateTimeImmutable;
use Fp4\PHP\PHPUnit as Assert;
use Fp4\PHP\PsalmIntegration as Type;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Combinator\constFalse;
use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\constTrue;
use function Fp4\PHP\Combinator\constVoid;
use function Fp4\PHP\Combinator\ctor;
use function Fp4\PHP\Combinator\id;
use function Fp4\PHP\Combinator\pipe;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

/**
 * @api
 */
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

    #[Test]
    public static function constVoid(): void
    {
        assertNull(constVoid());
    }

    #[Test]
    public static function id(): void
    {
        assertEquals(42, id(42));
    }

    #[Test]
    public static function constTrue(): void
    {
        assertTrue(constTrue());
    }

    #[Test]
    public static function constFalse(): void
    {
        assertFalse(constFalse());
    }

    #[Test]
    public static function ctor(): void
    {
        pipe(
            ctor(DateTimeImmutable::class),
            Type\isSameAs('Closure(string=, \DateTimeZone|null=): \DateTimeImmutable'),
            fn($createDateTime) => $createDateTime('2023-06-07'),
            Assert\equals(new DateTimeImmutable('2023-06-07')),
        );
    }
}
