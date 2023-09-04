<?php

declare(strict_types=1);

namespace Fp4\PHP\Test;

use DateTimeImmutable;
use Fp4\PHP\ArrayList as L;
use Fp4\PHP\PHPUnit as Assert;
use Fp4\PHP\PsalmIntegration as Type;
use Fp4\PHP\Shape as S;
use Fp4\PHP\Test\Fixture\InheritedObj;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Combinator\constFalse;
use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\constTrue;
use function Fp4\PHP\Combinator\constVoid;
use function Fp4\PHP\Combinator\ctor;
use function Fp4\PHP\Combinator\id;
use function Fp4\PHP\Combinator\pipe;
use function Fp4\PHP\Combinator\tupled;
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

    #[Test]
    public static function tupled(): void
    {
        pipe(
            L\from([
                S\from(['test1', 40]),
                S\from(['test2', 41]),
                S\from(['test3', 42]),
            ]),
            L\map(tupled(fn($a, $b) => new InheritedObj($a, $b))),
            Type\isSameAs('list<InheritedObj>'),
            Assert\equals([
                new InheritedObj(prop1: 'test1', prop2: 40),
                new InheritedObj(prop1: 'test2', prop2: 41),
                new InheritedObj(prop1: 'test3', prop2: 42),
            ]),
        );

        pipe(
            L\from([
                S\from(['test1', 40]),
                S\from(['test2', 41]),
                S\from(['test3', 42]),
            ]),
            L\map(
                tupled(ctor(InheritedObj::class)),
            ),
            Type\isSameAs('list<InheritedObj>'),
            Assert\equals([
                new InheritedObj(prop1: 'test1', prop2: 40),
                new InheritedObj(prop1: 'test2', prop2: 41),
                new InheritedObj(prop1: 'test3', prop2: 42),
            ]),
        );
    }
}
