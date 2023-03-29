<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\None;
use Fp4\PHP\Type\Option;
use Fp4\PHP\Type\Some;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use RuntimeException;
use function Fp4\PHP\Module\Functions\pipe;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertTrue;

final class OptionTest extends TestCase
{
    #[Test]
    public static function some(): void
    {
        $option = O\some(42);

        assertInstanceOf(Some::class, $option);
        assertEquals(42, pipe(
            $option,
            O\getOrNull(),
        ));
    }

    #[Test]
    public static function none(): void
    {
        assertInstanceOf(None::class, O\none);
        assertEquals(null, pipe(
            O\none,
            O\getOrNull(),
        ));
    }

    #[Test]
    public static function fromNullable(): void
    {
        assertEquals(null, pipe(
            O\fromNullable(null),
            O\getOrNull(),
        ));

        assertEquals(42, pipe(
            O\fromNullable(42),
            O\getOrNull(),
        ));
    }

    #[Test]
    public static function tryCatch(): void
    {
        assertEquals(null, pipe(
            O\tryCatch(fn() => throw new RuntimeException()),
            O\getOrNull(),
        ));

        assertEquals(42, pipe(
            O\tryCatch(fn() => 42),
            O\getOrNull(),
        ));
    }

    #[Test]
    public static function fold(): void
    {
        assertEquals('none', pipe(
            O\none,
            O\fold(fn() => 'none', fn() => 'some'),
        ));

        assertEquals('some: 42', pipe(
            O\some(42),
            O\fold(fn() => 'none', fn($value) => "some: {$value}"),
        ));
    }

    #[Test]
    public static function isSome(): void
    {
        $isSome = O\isSome(...);

        assertFalse(pipe(O\none, $isSome));
        assertTrue(pipe(O\some(42), $isSome));
    }

    #[Test]
    public static function isNone(): void
    {
        $isNone = O\isNone(...);

        assertFalse(pipe(O\some(42), $isNone));
        assertTrue(pipe(O\none, $isNone));
    }

    #[Test]
    public static function map(): void
    {
        assertEquals(42, pipe(
            O\some(0),
            O\map(fn($i) => $i + 42),
            O\getOrNull(),
        ));

        assertEquals(null, pipe(
            O\none,
            O\map(fn($i) => $i + 42),
            O\getOrNull(),
        ));
    }

    #[Test]
    public static function flatMap(): void
    {
        /** @var Option<int> */
        $option = O\some(0);

        assertEquals(42, pipe(
            $option,
            O\flatMap(fn($i) => $i === 0 ? O\some($i + 42) : O\none),
            O\getOrNull(),
        ));

        assertEquals(null, pipe(
            $option,
            O\flatMap(fn($i) => $i !== 0 ? O\some($i + 42) : O\none),
            O\getOrNull(),
        ));
    }

    #[Test]
    public static function orElse(): void
    {
        assertEquals(42, pipe(
            O\some(42),
            O\orElse(fn() => O\some(0)),
            O\getOrNull(),
        ));

        assertEquals(42, pipe(
            O\none,
            O\orElse(fn() => O\some(42)),
            O\getOrNull(),
        ));
    }
}
