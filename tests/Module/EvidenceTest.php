<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use ArrayObject;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Psalm as Type;
use Fp4\PHP\Type\Option;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SplFixedArray;
use stdClass;

use function Fp4\PHP\Module\Functions\pipe;
use function PHPUnit\Framework\assertEquals;

/**
 * @api
 */
final class EvidenceTest extends TestCase
{
    private const NULL = null;
    private const NUMERIC = '42';
    private const STRING = 'str';
    private const EMPTY_STRING = '';
    private const FLOAT = 42.00;
    private const FLOAT_MAX = PHP_FLOAT_MAX;
    private const FLOAT_MIN = PHP_FLOAT_MIN;
    private const INT = 42;
    private const INT_MAX = PHP_INT_MAX;
    private const INT_MIN = PHP_INT_MIN;
    private const TRUE = true;
    private const FALSE = false;
    private const EMPTY_LIST = [];

    #[Test]
    #[DataProvider('proveIntDataProvider')]
    public static function proveInt(mixed $value, Option $expected): void
    {
        assertEquals(
            $expected,
            pipe($value, Ev\proveInt(...)),
        );
    }

    public static function proveIntDataProvider(): array
    {
        return [
            [self::NULL, O\none],
            [self::NUMERIC, O\none],
            [self::STRING, O\none],
            [self::EMPTY_STRING, O\none],
            [self::FLOAT, O\none],
            [self::FLOAT_MAX, O\none],
            [self::FLOAT_MIN, O\none],
            [self::EMPTY_LIST, O\none],
            [self::INT, O\some(self::INT)],
            [self::INT_MAX, O\some(self::INT_MAX)],
            [self::INT_MIN, O\some(self::INT_MIN)],
        ];
    }

    #[Test]
    #[DataProvider('proveFloatDataProvider')]
    public static function proveFloat(mixed $value, Option $expected): void
    {
        assertEquals(
            $expected,
            pipe($value, Ev\proveFloat(...)),
        );
    }

    public static function proveFloatDataProvider(): array
    {
        return [
            [self::NULL, O\none],
            [self::NUMERIC, O\none],
            [self::STRING, O\none],
            [self::EMPTY_STRING, O\none],
            [self::INT, O\none],
            [self::INT_MAX, O\none],
            [self::INT_MIN, O\none],
            [self::EMPTY_LIST, O\none],
            [self::FLOAT, O\some(self::FLOAT)],
            [self::FLOAT_MAX, O\some(self::FLOAT_MAX)],
            [self::FLOAT_MIN, O\some(self::FLOAT_MIN)],
        ];
    }

    #[Test]
    #[DataProvider('proveStringDataProvider')]
    public static function proveString(mixed $value, Option $expected): void
    {
        assertEquals(
            $expected,
            pipe($value, Ev\proveString(...)),
        );
    }

    public static function proveStringDataProvider(): array
    {
        return [
            [self::NULL, O\none],
            [self::INT, O\none],
            [self::TRUE, O\none],
            [self::FALSE, O\none],
            [self::FLOAT, O\none],
            [self::EMPTY_LIST, O\none],
            [self::EMPTY_STRING, O\some(self::EMPTY_STRING)],
            [self::STRING, O\some(self::STRING)],
        ];
    }

    #[Test]
    #[DataProvider('proveNonEmptyStringDataProvider')]
    public static function proveNonEmptyString(mixed $value, Option $expected): void
    {
        assertEquals(
            $expected,
            pipe($value, Ev\proveNonEmptyString(...)),
        );
    }

    public static function proveNonEmptyStringDataProvider(): array
    {
        return [
            [self::NULL, O\none],
            [self::INT, O\none],
            [self::TRUE, O\none],
            [self::FALSE, O\none],
            [self::FLOAT, O\none],
            [self::EMPTY_LIST, O\none],
            [self::EMPTY_STRING, O\none],
            [self::STRING, O\some(self::STRING)],
        ];
    }

    #[Test]
    #[DataProvider('proveBoolDataProvider')]
    public static function proveBool(mixed $value, Option $expected): void
    {
        assertEquals(
            $expected,
            pipe($value, Ev\proveBool(...)),
        );
    }

    public static function proveBoolDataProvider(): array
    {
        return [
            [self::NULL, O\none],
            [self::INT, O\none],
            [self::FLOAT, O\none],
            [self::EMPTY_LIST, O\none],
            [self::EMPTY_STRING, O\none],
            [self::STRING, O\none],
            [self::TRUE, O\some(self::TRUE)],
            [self::FALSE, O\some(self::FALSE)],
        ];
    }

    #[Test]
    #[DataProvider('proveNullDataProvider')]
    public static function proveNull(mixed $value, Option $expected): void
    {
        assertEquals(
            $expected,
            pipe($value, Ev\proveNull(...)),
        );
    }

    public static function proveNullDataProvider(): array
    {
        return [
            [self::INT, O\none],
            [self::FLOAT, O\none],
            [self::EMPTY_LIST, O\none],
            [self::EMPTY_STRING, O\none],
            [self::STRING, O\none],
            [self::NULL, O\some(self::NULL)],
        ];
    }

    #[Test]
    #[DataProvider('proveObjectDataProvider')]
    public static function proveObject(mixed $value, Option $expected): void
    {
        assertEquals(
            $expected,
            pipe($value, Ev\proveObject(...)),
        );
    }

    public static function proveObjectDataProvider(): array
    {
        return [
            [self::INT, O\none],
            [self::FLOAT, O\none],
            [self::EMPTY_LIST, O\none],
            [self::EMPTY_STRING, O\none],
            [self::STRING, O\none],
            [new stdClass(), O\some(new stdClass())],
        ];
    }

    #[Test]
    public static function proveOf(): void
    {
        assertEquals(
            O\some(new stdClass()),
            pipe(new stdClass(), Ev\proveOf(stdClass::class)),
        );

        assertEquals(
            O\some(new ArrayObject()),
            pipe(new ArrayObject(), Ev\proveOf(ArrayObject::class)),
        );

        assertEquals(
            O\none,
            pipe(1, Ev\proveOf(ArrayObject::class)),
        );

        assertEquals(
            O\none,
            pipe(new stdClass(), Ev\proveOf(ArrayObject::class)),
        );
    }

    #[Test]
    public static function proveUnion(): void
    {
        $number = Ev\proveUnion([
            Ev\proveInt(...),
            Ev\proveFloat(...),
        ]);

        assertEquals(O\some(42), pipe(42, $number));
        assertEquals(O\some(42.00), pipe(42.00, $number));
        assertEquals(O\none, pipe('42.00', $number));
    }

    #[Test]
    public static function proveList(): void
    {
        assertEquals(O\none, pipe(
            ['fst' => 1, 'snd' => 2, 'thr' => 3],
            Ev\proveList(...),
        ));
        assertEquals(O\some([]), pipe(
            [],
            Ev\proveList(...),
        ));
        assertEquals(O\some([1, 2, 3]), pipe(
            [1, 2, 3],
            Ev\proveList(...),
        ));
    }

    #[Test]
    public static function proveListOf(): void
    {
        $integers = Ev\proveListOf(
            Ev\proveInt(...),
        );

        assertEquals(O\some([]), pipe([], $integers));
        assertEquals(O\some([1, 2, 3]), pipe([1, 2, 3], $integers));
        assertEquals(O\none, pipe([1, 2, '3'], $integers));
        assertEquals(O\none, pipe([100 => 1, 200 => 2, 300 => 3], $integers));
        assertEquals(O\none, pipe(3, $integers));
    }

    #[Test]
    public static function proveNonEmptyList(): void
    {
        assertEquals(O\none, pipe(
            ['fst' => 1, 'snd' => 2, 'thr' => 3],
            Ev\proveNonEmptyList(...),
        ));
        assertEquals(O\none, pipe(
            [],
            Ev\proveNonEmptyList(...),
        ));
        assertEquals(O\some([1, 2, 3]), pipe(
            [1, 2, 3],
            Ev\proveNonEmptyList(...),
        ));
    }

    #[Test]
    public static function proveNonEmptyListOf(): void
    {
        $integers = Ev\proveNonEmptyListOf(
            Ev\proveInt(...),
        );

        assertEquals(O\some([1, 2, 3]), pipe([1, 2, 3], $integers));
        assertEquals(O\none, pipe([], $integers));
        assertEquals(O\none, pipe([100 => 1, 200 => 2, 300 => 3], $integers));
        assertEquals(O\none, pipe(3, $integers));
    }

    #[Test]
    public static function proveArray(): void
    {
        assertEquals(O\none, pipe(
            SplFixedArray::fromArray([1, 2, 3]),
            Ev\proveArray(...),
        ));
        assertEquals(O\some(['fst' => 1, 'snd' => 2, 'thr' => 3]), pipe(
            ['fst' => 1, 'snd' => 2, 'thr' => 3],
            Ev\proveArray(...),
        ));
        assertEquals(O\some([]), pipe(
            [],
            Ev\proveArray(...),
        ));
        assertEquals(O\some([1, 2, 3]), pipe(
            [1, 2, 3],
            Ev\proveArray(...),
        ));
    }

    #[Test]
    public static function proveArrayOf(): void
    {
        $numMap = Ev\proveArrayOf(
            Ev\proveString(...),
            Ev\proveInt(...),
        );

        assertEquals(
            O\some(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            pipe(['fst' => 1, 'snd' => 2, 'thr' => 3], $numMap),
        );
        assertEquals(O\some([]), pipe([], $numMap));
        assertEquals(O\none, pipe([100 => 1, 200 => 2, 300 => 3], $numMap));
        assertEquals(O\none, pipe(3, $numMap));
    }

    #[Test]
    public static function proveNonEmptyArray(): void
    {
        assertEquals(O\none, pipe(
            SplFixedArray::fromArray([1, 2, 3]),
            Ev\proveNonEmptyArray(...),
        ));
        assertEquals(O\none, pipe(
            [],
            Ev\proveNonEmptyArray(...),
        ));
        assertEquals(O\some(['fst' => 1, 'snd' => 2, 'thr' => 3]), pipe(
            ['fst' => 1, 'snd' => 2, 'thr' => 3],
            Ev\proveNonEmptyArray(...),
        ));
        assertEquals(O\some([1, 2, 3]), pipe(
            [1, 2, 3],
            Ev\proveNonEmptyArray(...),
        ));
    }

    #[Test]
    public static function proveNonEmptyArrayOf(): void
    {
        $numMap = Ev\proveNonEmptyArrayOf(
            Ev\proveString(...),
            Ev\proveInt(...),
        );

        assertEquals(
            O\some(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            pipe(['fst' => 1, 'snd' => 2, 'thr' => 3], $numMap),
        );
        assertEquals(O\none, pipe([100 => 1, 200 => 2, 300 => 3], $numMap));
        assertEquals(O\none, pipe(3, $numMap));
    }

    #[Test]
    public static function proveTrue(): void
    {
        pipe(
            false,
            Ev\proveTrue(...),
            Type\isSameAs('Option<true>'),
            Assert\equals(O\none),
        );

        pipe(
            true,
            Ev\proveTrue(...),
            Type\isSameAs('Option<true>'),
            Assert\equals(O\some(true)),
        );
    }

    #[Test]
    public static function proveFalse(): void
    {
        pipe(
            true,
            Ev\proveFalse(...),
            Type\isSameAs('Option<false>'),
            Assert\equals(O\none),
        );

        pipe(
            false,
            Ev\proveFalse(...),
            Type\isSameAs('Option<false>'),
            Assert\equals(O\some(false)),
        );
    }
}
