<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Psalm as Type;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\None;
use Fp4\PHP\Type\Option;
use Fp4\PHP\Type\Some;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

use function Fp4\PHP\Module\Combinator\pipe;

/**
 * @api
 * @see Option, Bindable
 */
final class OptionTest extends TestCase
{
    // region: constructor

    #[Test]
    public static function some(): void
    {
        pipe(
            O\some(42),
            Type\isSameAs('Option<int>'),
            O\getOrNull(...),
            Assert\equals(42),
        );
    }

    #[Test]
    public static function none(): void
    {
        pipe(
            O\none,
            Type\isSameAs('Option<never>'),
            O\getOrNull(...),
            Assert\equals(null),
        );
    }

    #[Test]
    public static function fromNullable(): void
    {
        pipe(
            O\fromNullable(42),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\fromNullable(null),
            Type\isSameAs('Option<never>'),
            Assert\equals(O\none),
        );
    }

    #[Test]
    public static function tryCatch(): void
    {
        pipe(
            O\tryCatch(fn() => throw new RuntimeException()),
            Type\isSameAs('Option<never>'),
            O\getOrNull(...),
            Assert\equals(null),
        );

        pipe(
            O\tryCatch(fn() => 42),
            Type\isSameAs('Option<int>'),
            O\getOrNull(...),
            Assert\equals(42),
        );
    }

    #[Test]
    public static function first(): void
    {
        pipe(
            O\first(
                fn() => O\none,
            ),
            Type\isSameAs('Option<never>'),
            Assert\equals(O\none),
        );

        pipe(
            O\first(
                fn() => O\none,
                fn() => O\some(42),
                fn() => O\some('str'),
            ),
            Type\isSameAs('Option<int|string>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\first(
                fn() => O\none,
                fn() => O\none,
                fn() => O\some(42),
            ),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );
    }

    // endregion: constructor

    // region: destructors

    #[Test]
    public static function getOrNull(): void
    {
        pipe(
            O\some(42),
            O\getOrNull(...),
            Type\isSameAs('int|null'),
            Assert\equals(42),
        );

        pipe(
            O\none,
            O\getOrNull(...),
            Type\isSameAs('null'),
            Assert\equals(null),
        );
    }

    #[Test]
    public static function getOrElse(): void
    {
        pipe(
            O\some(42),
            O\getOrElse(0),
            Type\isSameAs('int'),
            Assert\equals(42),
        );

        pipe(
            O\none,
            O\getOrElse(42),
            Type\isSameAs('42'),
            Assert\equals(42),
        );
    }

    #[Test]
    public static function getOrCall(): void
    {
        pipe(
            O\some(42),
            O\getOrCall(fn() => 0),
            Type\isSameAs('int'),
            Assert\equals(42),
        );

        pipe(
            O\none,
            O\getOrCall(fn() => 42),
            Type\isSameAs('42'),
            Assert\equals(42),
        );
    }

    // endregion: destructors

    // region: refinements

    #[Test]
    public static function isSome(): void
    {
        pipe(
            O\none,
            O\isSome(...),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );

        pipe(
            O\some(42),
            O\isSome(...),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );
    }

    #[Test]
    public static function isNone(): void
    {
        pipe(
            O\some(42),
            O\isNone(...),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );

        pipe(
            O\none,
            O\isNone(...),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );
    }

    // endregion: refinements

    // region: ops

    #[Test]
    public static function map(): void
    {
        pipe(
            O\some(0),
            O\map(fn($i) => $i + 42),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\none,
            O\map(fn($i) => $i),
            Type\isSameAs('Option<never>'),
            Assert\equals(O\none),
        );
    }

    #[Test]
    public static function tap(): void
    {
        $expected = new stdClass();
        $expected->value = 42;

        $actual = new stdClass();

        pipe($expected, Assert\equals($expected));

        pipe(
            O\some($actual),
            O\tap(fn(stdClass $obj): int => $obj->value = 42),
            Type\isSameAs('Option<stdClass>'),
            Assert\instance(Some::class),
        );

        pipe($expected, Assert\equals($actual));

        pipe(
            O\none,
            O\tap(fn(stdClass $obj) => print_r($obj)),
            Type\isSameAs('Option<never>'),
            Assert\instance(None::class),
        );
    }

    #[Test]
    public static function flatMap(): void
    {
        pipe(
            O\some(0),
            O\flatMap(fn($i) => 0 === $i ? O\some($i + 42) : O\none),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\some(0),
            O\flatMap(fn($i) => 0 !== $i ? O\some($i + 42) : O\none),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\none),
        );
    }

    #[Test]
    public static function flatMapNullable(): void
    {
        pipe(
            O\none,
            O\flatMapNullable(fn($i) => $i),
            Type\isSameAs('Option<never>'),
            Assert\equals(O\none),
        );

        pipe(
            O\some(0),
            O\flatMapNullable(fn($i) => 1 !== $i ? $i + 42 : null),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\some(0),
            O\flatMapNullable(fn($i) => 0 !== $i ? $i + 42 : null),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\none),
        );
    }

    #[Test]
    public static function flatten(): void
    {
        pipe(
            O\none,
            O\flatten(...),
            Assert\equals(O\none),
        );

        pipe(
            O\some(O\none),
            O\flatten(...),
            Assert\equals(O\none),
        );

        pipe(
            O\some(O\some(42)),
            O\flatten(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );
    }

    #[Test]
    public static function ap(): void
    {
        pipe(
            O\none,
            O\ap(O\some(42)),
            Assert\equals(O\none),
        );

        pipe(
            O\some(fn(int $a) => $a + 1),
            O\ap(O\none),
            Assert\equals(O\none),
        );

        pipe(
            O\none,
            O\ap(O\none),
            Assert\equals(O\none),
        );

        pipe(
            O\some(fn(int $a) => $a + 1),
            O\ap(O\some(41)),
            Assert\equals(O\some(42)),
        );
    }

    #[Test]
    public static function orElse(): void
    {
        pipe(
            O\some(42),
            O\orElse(fn() => O\some(0)),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\none,
            O\orElse(fn() => O\some(42)),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\none,
            O\orElse(fn() => O\none),
            Type\isSameAs('Option<never>'),
            Assert\equals(O\none),
        );
    }

    #[Test]
    public static function filter(string|int $stringOrInt = 42): void
    {
        pipe(
            O\some($stringOrInt),
            O\filter(is_int(...)),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\some(42),
            O\filter(fn($i) => $i >= 0 && $i <= 100),
            Type\isSameAs('Option<int<0, 100>>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\none,
            O\filter(fn($i) => $i >= 42),
            Type\isSameAs('Option<never>'),
            Assert\equals(O\none),
        );

        pipe(
            O\some(42),
            O\filter(fn($i) => $i > 42),
            Type\isSameAs('Option<int<43, max>>'),
            Assert\equals(O\none),
        );
    }

    #[Test]
    public static function filterOf(): void
    {
        pipe(
            O\some(42),
            O\filterOf(stdClass::class),
            Type\isSameAs('Option<stdClass>'),
            Assert\equals(O\none),
        );

        pipe(
            O\some(new stdClass()),
            O\filterOf(stdClass::class),
            Type\isSameAs('Option<stdClass>'),
            Assert\equals(O\some(new stdClass())),
        );
    }

    // endregion: ops

    // region: bindable

    #[Test]
    public static function bind(): void
    {
        pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(31),
                b: fn() => O\none,
            ),
            Type\isSameAs('Option<Bindable<object{a: int, b: never}>>'),
            Assert\equals(O\none),
        );

        pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(31),
                b: fn() => O\none,
            ),
            O\bind(c: fn() => O\some(32)),
            Type\isSameAs('Option<Bindable<object{a: int, b: never, c: int}>>'),
            Assert\equals(O\none),
        );

        pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(31),
                b: fn() => O\some(11),
            ),
            Type\isSameAs('Option<Bindable<object{a: int, b: int}>>'),
            O\map(fn($i) => $i->a + $i->b),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(30),
                b: fn() => O\some(10),
                c: fn($i) => O\some($i->a + $i->b + 2),
            ),
            Type\isSameAs('Option<Bindable<object{a: int, b: int, c: int}>>'),
            O\map(fn($i) => $i->c),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(42)),
        );
    }

    #[Test]
    public static function let(): void
    {
        pipe(
            O\bindable(),
            O\bind(
                a: fn() => O\some(31),
                b: fn() => O\none,
            ),
            O\let(c: fn() => 42),
            Type\isAssignableTo('Option<Bindable<object{a: int, b: never, c: int}>>'),
            Assert\equals(O\none),
        );

        pipe(
            O\bindable(),
            O\let(
                a: fn() => 31,
                b: fn() => 11,
            ),
            Type\isAssignableTo('Option<Bindable<object{a: int, b: int}>>'),
            O\map(fn($i) => $i->a + $i->b),
            Type\isAssignableTo('Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            O\bindable(),
            O\let(
                a: fn() => 30,
                b: fn() => 10,
                c: fn($i) => $i->a + $i->b + 2,
            ),
            Type\isAssignableTo('Option<Bindable<object{a: int, b: int, c: int}>>'),
            O\map(fn($i) => $i->c),
            Type\isAssignableTo('Option<int>'),
            Assert\equals(O\some(42)),
        );
    }

    // endregion: bindable
}
