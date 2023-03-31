<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use ArrayObject;
use Fp4\PHP\Module\Evidence;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
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
            pipe($value, Evidence\proveInt(...)),
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
            pipe($value, Evidence\proveFloat(...)),
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
            pipe($value, Evidence\proveString(...)),
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
            pipe($value, Evidence\proveNonEmptyString(...)),
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
            pipe($value, Evidence\proveBool(...)),
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
            pipe($value, Evidence\proveNull(...)),
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
            pipe($value, Evidence\proveObject(...)),
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
            pipe(new stdClass(), Evidence\proveOf(stdClass::class)),
        );

        assertEquals(
            O\some(new ArrayObject()),
            pipe(new ArrayObject(), Evidence\proveOf(ArrayObject::class)),
        );

        assertEquals(
            O\none,
            pipe(1, Evidence\proveOf(ArrayObject::class)),
        );

        assertEquals(
            O\none,
            pipe(new stdClass(), Evidence\proveOf(ArrayObject::class)),
        );
    }

    #[Test]
    public static function proveUnion(): void
    {
        $number = Evidence\proveUnion([
            Evidence\proveInt(...),
            Evidence\proveFloat(...),
        ]);

        assertEquals(O\some(42), pipe(42, $number));
        assertEquals(O\some(42.00), pipe(42.00, $number));
        assertEquals(O\none, pipe('42.00', $number));
    }

    #[Test]
    public static function proveList(): void
    {
        $integers = Evidence\proveList(
            Evidence\proveInt(...),
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
        $integers = Evidence\proveNonEmptyList(
            Evidence\proveInt(...),
        );

        assertEquals(O\some([1, 2, 3]), pipe([1, 2, 3], $integers));
        assertEquals(O\none, pipe([], $integers));
        assertEquals(O\none, pipe([100 => 1, 200 => 2, 300 => 3], $integers));
        assertEquals(O\none, pipe(3, $integers));
    }

    #[Test]
    public static function proveArray(): void
    {
        $numMap = Evidence\proveArray(
            Evidence\proveString(...),
            Evidence\proveInt(...),
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
        $numMap = Evidence\proveNonEmptyArray(
            Evidence\proveString(...),
            Evidence\proveInt(...),
        );

        assertEquals(
            O\some(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            pipe(['fst' => 1, 'snd' => 2, 'thr' => 3], $numMap),
        );
        assertEquals(O\none, pipe([100 => 1, 200 => 2, 300 => 3], $numMap));
        assertEquals(O\none, pipe(3, $numMap));
    }
}
