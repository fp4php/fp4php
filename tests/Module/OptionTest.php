<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\None;
use Fp4\PHP\Type\Some;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

use function Fp4\PHP\Module\Functions\pipe;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertTrue;

/**
 * @api
 */
final class OptionTest extends TestCase
{
    #[Test]
    public static function some(): void
    {
        $option = O\some(42);
        /** @psalm-check-type-exact $option = \Fp4\PHP\Type\Option<int> */
        assertInstanceOf(Some::class, $option);
        assertEquals(42, pipe(
            $option,
            O\getOrNull(...),
        ));
    }

    #[Test]
    public static function none(): void
    {
        assertInstanceOf(None::class, O\none);

        assertEquals(null, pipe(
            O\none,
            O\getOrNull(...),
        ));
    }

    #[Test]
    public static function fromLiteral(): void
    {
        $fortyTwo = O\fromLiteral(42);
        /** @psalm-check-type-exact $fortyTwo = \Fp4\PHP\Type\Option<42> */
        $null = O\fromLiteral(null);
        /** @psalm-check-type-exact $null = \Fp4\PHP\Type\Option<null> */
        assertEquals(O\some(42), $fortyTwo);
        assertEquals(O\some(null), $null);
    }

    #[Test]
    public static function fromNullableLiteral(): void
    {
        $fortyTwo = O\fromNullableLiteral(42);
        /** @psalm-check-type-exact $fortyTwo = \Fp4\PHP\Type\Option<42> */
        $none = O\fromNullableLiteral(null);
        /** @psalm-check-type-exact $none = \Fp4\PHP\Type\None */
        assertEquals(O\some(42), $fortyTwo);
        assertEquals(O\none, $none);
    }

    #[Test]
    public static function fromNullable(): void
    {
        $num = O\fromNullable(42);
        /** @psalm-check-type-exact $num = \Fp4\PHP\Type\Option<int> */
        $none = O\fromNullable(null);
        /** @psalm-check-type-exact $none = \Fp4\PHP\Type\None */
        assertEquals(O\some(42), $num);
        assertEquals(O\none, $none);
    }

    #[Test]
    public static function tryCatch(): void
    {
        assertEquals(null, pipe(
            O\tryCatch(fn() => throw new RuntimeException()),
            O\getOrNull(...),
        ));

        assertEquals(42, pipe(
            O\tryCatch(fn() => 42),
            O\getOrNull(...),
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
        assertFalse(pipe(
            O\none,
            O\isSome(...),
        ));

        assertTrue(pipe(
            O\some(42),
            O\isSome(...),
        ));
    }

    #[Test]
    public static function isNone(): void
    {
        assertFalse(pipe(
            O\some(42),
            O\isNone(...),
        ));

        assertTrue(pipe(
            O\none,
            O\isNone(...),
        ));
    }

    #[Test]
    public static function map(): void
    {
        assertEquals(42, pipe(
            O\some(0),
            O\map(fn($i) => $i + 42),
            O\getOrNull(...),
        ));

        assertEquals(null, pipe(
            O\none,
            O\map(fn($i) => $i + 42),
            O\getOrNull(...),
        ));
    }

    #[Test]
    public static function tap(): void
    {
        $expected = new stdClass();
        $expected->value = 42;

        $actual = new stdClass();

        assertInstanceOf(Some::class, pipe(
            O\some($actual),
            O\tap(fn($obj) => $obj->value = 42),
        ));

        assertEquals($expected, $actual);

        assertInstanceOf(None::class, pipe(
            O\none,
            O\tap(fn(stdClass $obj) => print_r($obj)),
        ));
    }

    #[Test]
    public static function flatMap(): void
    {
        assertEquals(42, pipe(
            O\some(0),
            O\flatMap(fn($i) => 0 === $i ? O\some($i + 42) : O\none),
            O\getOrNull(...),
        ));

        assertEquals(null, pipe(
            O\some(0),
            O\flatMap(fn($i) => 0 !== $i ? O\some($i + 42) : O\none),
            O\getOrNull(...),
        ));
    }

    #[Test]
    public static function orElse(): void
    {
        assertEquals(42, pipe(
            O\some(42),
            O\orElse(fn() => O\some(0)),
            O\getOrNull(...),
        ));

        assertEquals(42, pipe(
            O\none,
            O\orElse(fn() => O\some(42)),
            O\getOrNull(...),
        ));
    }

    #[Test]
    public static function filter(): void
    {
        assertInstanceOf(None::class, pipe(
            O\some(42),
            O\filter(fn($i) => $i > 50),
        ));

        assertInstanceOf(None::class, pipe(
            O\none,
            O\filter(fn($i) => $i >= 42),
        ));

        assertEquals(42, pipe(
            O\some(42),
            O\filter(fn($i) => $i >= 42),
            O\getOrNull(...),
        ));
    }

    #[Test]
    public static function getOrElse(): void
    {
        assertEquals(42, pipe(
            O\some(42),
            O\getOrElse(0),
        ));

        assertEquals(42, pipe(
            O\none,
            O\getOrElse(42),
        ));
    }

    #[Test]
    public static function getOrCall(): void
    {
        assertEquals(42, pipe(
            O\some(42),
            O\getOrCall(fn() => 0),
        ));

        assertEquals(42, pipe(
            O\none,
            O\getOrCall(fn() => 42),
        ));
    }

    #[Test]
    public static function when(): void
    {
        assertEquals(O\none, O\when(false, fn() => 42));
        assertEquals(O\some(42), O\when(true, fn() => 42));
    }

    #[Test]
    public static function first(): void
    {
        assertEquals(O\none, O\first(
            fn() => O\none,
        ));

        assertEquals(O\none, O\first(
            fn() => O\none,
            fn() => O\none,
        ));

        assertEquals(O\some(42), O\first(
            fn() => O\none,
            fn() => O\some(42),
            fn() => O\some(42),
        ));

        assertEquals(O\some(42), O\first(
            fn() => O\none,
            fn() => O\none,
            fn() => O\some(42),
        ));
    }

    #[Test]
    public function bind(): void
    {
        assertEquals(O\none, pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(31),
                b: fn() => O\none,
            ),
        ));

        assertEquals(O\none, pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(31),
                b: fn() => O\none,
            ),
            O\bind(c: fn() => O\some(32)),
        ));

        assertEquals(O\some(42), pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(31),
                b: fn() => O\some(11),
            ),
            O\map(fn($i) => $i->a + $i->b),
        ));

        assertEquals(O\some(42), pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(30),
                b: fn() => O\some(10),
                c: fn($i) => O\some($i->a + $i->b + 2),
            ),
            O\map(fn($i) => $i->c),
        ));
    }

    #[Test]
    public function let(): void
    {
        assertEquals(O\none, pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(31),
                b: fn() => O\none,
            ),
            O\let(c: fn() => 32),
        ));

        assertEquals(O\some(42), pipe(
            O\bindable(),
            O\let(
                a: fn() => 31,
                b: fn() => 11,
            ),
            O\map(fn($i) => $i->a + $i->b),
        ));

        assertEquals(O\some(42), pipe(
            O\bindable(),
            O\let(
                a: fn() => 30,
                b: fn() => 10,
                c: fn($i) => $i->a + $i->b + 2,
            ),
            O\map(fn($i) => $i->c),
        ));
    }
}
