<?php

declare(strict_types=1);

namespace Fp4\PHP\Test;

use ArrayObject;
use Fp4\PHP\Either as E;
use Fp4\PHP\Option as O;
use Fp4\PHP\PHPUnit as Assert;
use Fp4\PHP\Psalm as Type;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function Fp4\PHP\Combinator\pipe;

/**
 * @api
 */
final class EitherTest extends TestCase
{
    // region: constructor

    #[Test]
    public static function left(): void
    {
        pipe(
            E\left(42),
            Type\isSameAs('E\Either<int, never>'),
            Assert\instance(E\Either::class),
            Assert\instance(E\Left::class),
        );
    }

    #[Test]
    public static function right(): void
    {
        pipe(
            E\right(42),
            Type\isSameAs('E\Either<never, int>'),
            Assert\instance(E\Either::class),
            Assert\instance(E\Right::class),
        );
    }

    #[Test]
    public static function tryCatch(): void
    {
        pipe(
            E\tryCatch(fn() => throw new RuntimeException('err')),
            Type\isSameAs('E\Either<\Throwable, never>'),
            Assert\equals(E\left(new RuntimeException('err'))),
        );

        pipe(
            E\tryCatch(fn() => 42),
            Type\isSameAs('E\Either<\Throwable, int>'),
            Assert\equals(E\right(42)),
        );
    }

    // endregion: constructor

    // region: destructor

    #[Test]
    public static function unwrap(): void
    {
        pipe(
            E\left(42),
            E\unwrap(...),
            Type\isSameAs('int'),
            Assert\equals(42),
        );

        pipe(
            E\right(42),
            E\unwrap(...),
            Type\isSameAs('int'),
            Assert\equals(42),
        );
    }

    #[Test]
    public static function getLeft(): void
    {
        pipe(
            E\left(42),
            E\getLeft(...),
            Type\isSameAs('O\Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            E\right(42),
            E\getLeft(...),
            Type\isSameAs('O\Option<never>'),
            Assert\equals(O\none),
        );
    }

    #[Test]
    public static function getRight(): void
    {
        pipe(
            E\right(42),
            E\getRight(...),
            Type\isSameAs('O\Option<int>'),
            Assert\equals(O\some(42)),
        );

        pipe(
            E\left(42),
            E\getRight(...),
            Type\isSameAs('O\Option<never>'),
            Assert\equals(O\none),
        );
    }

    // endregion: destructor

    // region: refinements

    #[Test]
    public static function isLeft(): void
    {
        pipe(
            E\left(42),
            E\isLeft(...),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            E\right(42),
            E\isLeft(...),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function isRight(): void
    {
        pipe(
            E\right(42),
            E\isRight(...),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            E\left(42),
            E\isRight(...),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    // endregion: refinements

    // region: ops

    #[Test]
    public static function map(): void
    {
        pipe(
            E\right(41),
            E\map(fn($i) => $i + 1),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            E\left(41),
            E\map(fn($i) => [$i]),
            Type\isSameAs('E\Either<int, array{never}>'),
            Assert\equals(E\left(41)),
        );
    }

    #[Test]
    public static function mapLeft(): void
    {
        pipe(
            E\left(41),
            E\mapLeft(fn($i) => $i + 1),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );

        pipe(
            E\right(41),
            E\mapLeft(fn($i) => [$i]),
            Type\isSameAs('E\Either<array{never}, int>'),
            Assert\equals(E\right(41)),
        );
    }

    #[Test]
    public static function tap(): void
    {
        $object = new ArrayObject();

        pipe(
            E\right(42),
            E\tap(fn($a) => $object->append($a)),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            $object,
            Assert\equals(new ArrayObject([42])),
        );

        pipe(
            E\left(42),
            E\tap(fn($a) => $object->append($a)),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );

        pipe(
            $object,
            Assert\equals(new ArrayObject([42])),
        );
    }

    #[Test]
    public static function tapLeft(): void
    {
        $object = new ArrayObject();

        pipe(
            E\left(42),
            E\tapLeft(fn($a) => $object->append($a)),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );

        pipe(
            $object,
            Assert\equals(new ArrayObject([42])),
        );

        pipe(
            E\right(42),
            E\tapLeft(fn($a) => $object->append($a)),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            $object,
            Assert\equals(new ArrayObject([42])),
        );
    }

    #[Test]
    public static function flatMap(): void
    {
        pipe(
            E\right(41),
            E\flatMap(fn($i) => E\right($i + 1)),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            E\left(41),
            E\flatMap(fn($i) => E\right($i + 1)),
            Type\isSameAs('E\Either<int, int>'),
            Assert\equals(E\left(41)),
        );

        pipe(
            E\right(41),
            E\flatMap(fn($i) => E\left($i + 1)),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );
    }

    #[Test]
    public static function flatten(): void
    {
        pipe(
            E\right(E\right(42)),
            E\flatten(...),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            E\right(E\left(42)),
            E\flatten(...),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );

        /**
         * @psalm-suppress CheckType
         * todo: The type Either<int|mixed, mixed> is not exactly the same as the type Either<int, never>
         */
        pipe(
            E\left(42),
            E\flatten(...),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );
    }

    #[Test]
    public static function ap(): void
    {
        pipe(
            E\right(fn(int $i) => $i + 1),
            E\ap(E\right(41)),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            E\right(fn(int $i) => $i + 1),
            E\ap(E\left(41)),
            Type\isSameAs('E\Either<int, int>'),
            Assert\equals(E\left(41)),
        );

        pipe(
            E\left(42),
            E\ap(E\right(41)),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );

        pipe(
            E\left(42),
            E\ap(E\left(41)),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );
    }

    #[Test]
    public static function orElse(): void
    {
        pipe(
            E\right(42),
            E\orElse(fn() => E\right(422)),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            E\left(442),
            E\orElse(fn() => E\right(42)),
            Type\isSameAs('E\Either<int, int>'),
            Assert\equals(E\right(42)),
        );
    }

    #[Test]
    public static function filterOrElse(): void
    {
        pipe(
            E\right(42),
            E\filterOrElse(fn($i) => $i >= 42, fn() => 'never-left'),
            Type\isSameAs('E\Either<"never-left", int<42, max>>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            E\right(41),
            E\filterOrElse(fn($i) => $i >= 42, fn() => 'must be greater or equal to 42'),
            Type\isSameAs('E\Either<"must be greater or equal to 42", int<42, max>>'),
            Assert\equals(E\left('must be greater or equal to 42')),
        );
    }

    #[Test]
    public static function swap(): void
    {
        pipe(
            E\right(42),
            E\swap(...),
            Type\isSameAs('E\Either<int, never>'),
            Assert\equals(E\left(42)),
        );

        pipe(
            E\left(42),
            E\swap(...),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );
    }

    #[Test]
    public static function bind(): void
    {
        pipe(
            E\bindable(),
            E\bind(
                a: fn() => E\right(41),
                b: fn() => E\right(1),
            ),
            E\map(fn($i) => $i->a + $i->b),
            Type\isSameAs('E\Either<never, int>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            E\bindable(),
            E\bind(
                b: fn() => E\left('not-a-number'),
            ),
            E\bind(
                a: fn() => E\right(41),
            ),
            E\map(fn($i) => $i->a + $i->b),
            Type\isSameAs('E\Either<string, int>'),
            Assert\equals(E\left('not-a-number')),
        );
    }

    #[Test]
    public static function let(): void
    {
        pipe(
            E\bindable(),
            E\let(
                a: fn() => 1,
                b: fn() => 41,
            ),
            E\map(fn($i) => $i->a + $i->b),
            Type\isSameAs('E\Either<never, 42>'),
            Assert\equals(E\right(42)),
        );

        pipe(
            E\bindable(),
            E\bind(c: fn() => E\left('shor-circuit')),
            E\let(
                a: fn() => 1,
                b: fn() => 41,
            ),
            E\map(fn($i) => $i->a + $i->b),
            Type\isSameAs('E\Either<string, 42>'),
            Assert\equals(E\left('shor-circuit')),
        );
    }

    // endregion: ops
}
