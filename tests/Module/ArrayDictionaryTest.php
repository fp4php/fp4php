<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use ArrayObject;
use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Psalm as Type;
use Fp4\PHP\Type\Option;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Module\Functions\pipe;
use function is_int;

/**
 * @api
 * @use Option
 */
final class ArrayDictionaryTest extends TestCase
{
    // region: constructor

    #[Test]
    public static function fromIterable(): void
    {
        pipe(
            D\fromIterable(new ArrayObject(['fst' => 1, 'snd' => 2, 'thr' => 3])),
            Type\isSameAs('array<non-empty-string, int>'),
            Assert\same(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );
    }

    #[Test]
    public static function from(): void
    {
        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            Type\isSameAs('non-empty-array<non-empty-string, int>'),
            Assert\same(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );
    }

    #[Test]
    public static function fromLiteral(): void
    {
        pipe(
            D\fromLiteral(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            Type\isSameAs('non-empty-array<"fst"|"snd"|"thr", 1|2|3>'),
            Assert\same(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );
    }

    // endregion: constructor

    // region: ops

    #[Test]
    public static function map(): void
    {
        pipe(
            D\from([]),
            D\map(fn($num) => ['num' => $num]),
            Type\isSameAs('array<never, array{num: never}>'),
            Assert\same([]),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\map(fn($num) => ['num' => $num]),
            Type\isSameAs('non-empty-array<non-empty-string, array{num: int}>'),
            Assert\same([
                'fst' => ['num' => 1],
                'snd' => ['num' => 2],
                'thr' => ['num' => 3],
            ]),
        );
    }

    #[Test]
    public static function mapKV(): void
    {
        pipe(
            D\from([]),
            D\mapKV(fn($key, $num) => ['num' => $num, 'key' => $key]),
            Type\isSameAs('array<never, array{num: never, key: never}>'),
            Assert\same([]),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\mapKV(fn($key, $num) => ['num' => $num, 'key' => "k-{$key}"]),
            Type\isSameAs('non-empty-array<non-empty-string, array{num: int, key: non-empty-string}>'),
            Assert\same([
                'fst' => ['num' => 1, 'key' => 'k-fst'],
                'snd' => ['num' => 2, 'key' => 'k-snd'],
                'thr' => ['num' => 3, 'key' => 'k-thr'],
            ]),
        );
    }

    #[Test]
    public static function flatMap(): void
    {
        pipe(
            D\from([]),
            D\flatMap(fn($_) => D\from([])),
            Type\isSameAs('array<never, never>'),
            Assert\same([]),
        );

        pipe(
            D\fromLiteral(['fst' => 1, 'snd' => 2, 'thr' => 3, 'fth' => 4]),
            D\flatMap(fn($num) => match ($num) {
                1 => D\from([10 => $num - 1, 20 => $num + 0, 30 => $num + 1]),
                2 => D\from([40 => $num - 1, 50 => $num + 0, 60 => $num + 1]),
                3 => D\from([70 => $num - 1, 80 => $num + 0, 90 => $num + 1]),
                default => D\from([]),
            }),
            Type\isSameAs('array<int, int>'),
            Assert\same([
                10 => 0, 20 => 1, 30 => 2,
                40 => 1, 50 => 2, 60 => 3,
                70 => 2, 80 => 3, 90 => 4,
            ]),
        );
    }

    #[Test]
    public static function flatMapKV(): void
    {
        pipe(
            D\from([]),
            D\flatMapKV(fn($_k, $_v) => D\from([])),
            Type\isSameAs('array<never, never>'),
            Assert\same([]),
        );

        pipe(
            D\fromLiteral(['fst' => 1, 'snd' => 2, 'thr' => 3, 'fth' => 4]),
            D\flatMapKV(fn($key, $num) => match ($key) {
                'fst' => D\from([10 => $num - 1, 20 => $num + 0, 30 => $num + 1]),
                'snd' => D\from([40 => $num - 1, 50 => $num + 0, 60 => $num + 1]),
                'thr' => D\from([70 => $num - 1, 80 => $num + 0, 90 => $num + 1]),
                default => D\from([]),
            }),
            Type\isSameAs('array<int, int>'),
            Assert\same([
                10 => 0, 20 => 1, 30 => 2,
                40 => 1, 50 => 2, 60 => 3,
                70 => 2, 80 => 3, 90 => 4,
            ]),
        );
    }

    #[Test]
    public function filter(): void
    {
        pipe(
            D\from([]),
            D\filter(fn($_) => true),
            Type\isSameAs('array<never, never>'),
            Assert\same([]),
        );

        pipe(
            D\from([1, 2, 3, 4, 5, 6, 7, 8]),
            D\filter(fn($num) => 0 === $num % 2),
            Type\isSameAs('array<int, int>'),
            Assert\same([1 => 2, 3 => 4, 5 => 6, 7 => 8]),
        );

        /**
         * @psalm-suppress CheckType
         * todo: array<empty-mixed, int> instead of array<non-empty-string, int>
         */
        pipe(
            D\from(['fst' => 1, 'snd' => '2', 'thr' => 3]),
            Type\isSameAs('non-empty-array<non-empty-string, int|non-empty-string>'),
            D\filter(is_int(...)),
            Type\isSameAs('array<non-empty-string, int>'),
            Assert\same(['fst' => 1, 'thr' => 3]),
        );
    }

    #[Test]
    public function filterKV(): void
    {
        pipe(
            D\from([]),
            D\filterKV(fn($_k, $_v) => true),
            Type\isSameAs('array<never, never>'),
            Assert\same([]),
        );

        pipe(
            D\from([1, 2, 3, 4, 5, 6, 7, 8]),
            D\filterKV(fn($key, $_) => 0 === $key % 2),
            Type\isSameAs('array<int, int>'),
            Assert\same([0 => 1, 2 => 3, 4 => 5, 6 => 7]),
        );

        pipe(
            D\from([1 => 1, 'snd' => 2, 3 => 3]),
            Type\isSameAs('non-empty-array<int|non-empty-string, int>'),
            D\filterKV(fn($key, $_val) => is_int($key)),
            Type\isSameAs('array<int, int>'),
            Assert\same([1 => 1, 3 => 3]),
        );
    }

    #[Test]
    public function get(): void
    {
        pipe(
            D\from([]),
            D\get('by-key'),
            Type\isSameAs('Option<never>'),
            Assert\same(O\none),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\get('fst'),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(1)),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => '2', 'thr' => 3]),
            D\get('fst'),
            Type\isSameAs('Option<int|non-empty-string>'),
            Assert\equals(O\some(1)),
        );
    }

    #[Test]
    public function prepend(): void
    {
        pipe(
            D\from([]),
            D\prepend('key', 42),
            Type\isSameAs('non-empty-array<"key", 42>'),
            Assert\same(['key' => 42]),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => 2]),
            D\prepend('thr', 3),
            Type\isSameAs('non-empty-array<non-empty-string, int>'),
            Assert\same(['thr' => 3, 'fst' => 1, 'snd' => 2]),
        );
    }

    #[Test]
    public function append(): void
    {
        pipe(
            D\from([]),
            D\append('key', 42),
            Type\isSameAs('non-empty-array<"key", 42>'),
            Assert\same(['key' => 42]),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => 2]),
            D\append('thr', 3),
            Type\isSameAs('non-empty-array<non-empty-string, int>'),
            Assert\same(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );
    }

    // endregion: ops

    // region: terminal ops

    #[Test]
    public function keys(): void
    {
        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\keys(...),
            Type\isSameAs('non-empty-list<non-empty-string>'),
            Assert\same(['fst', 'snd', 'thr']),
        );
    }

    #[Test]
    public function values(): void
    {
        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\values(...),
            Type\isSameAs('non-empty-list<int>'),
            Assert\same([1, 2, 3]),
        );
    }

    #[Test]
    public function keyExists(): void
    {
        pipe(
            D\from([]),
            D\keyExists('fst'),
            Type\isSameAs('bool'),
            Assert\same(false),
        );

        pipe(
            D\from(['fst' => 1]),
            D\keyExists('fst'),
            Type\isSameAs('bool'),
            Assert\same(true),
        );
    }

    #[Test]
    public static function traverseOption(): void
    {
        $proveEven = fn(int $i): Option => 0 === $i % 2
            ? O\some($i)
            : O\none;

        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\traverseOption($proveEven),
            Type\isSameAs('Option<non-empty-array<non-empty-string, int>>'),
            Assert\equals(O\none)
        );

        pipe(
            D\from(['fst' => 2, 'snd' => 4, 'thr' => 6]),
            D\traverseOption($proveEven),
            Type\isSameAs('Option<non-empty-array<non-empty-string, int>>'),
            Assert\equals(O\some(['fst' => 2, 'snd' => 4, 'thr' => 6]))
        );
    }

    // endregion: terminal ops
}
