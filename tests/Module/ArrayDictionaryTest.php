<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use ArrayObject;
use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\Either as E;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Psalm as Type;
use Fp4\PHP\Module\Shape as S;
use Fp4\PHP\Type\Option;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Fp4\PHP\Module\Functions\constTrue;
use function Fp4\PHP\Module\Functions\pipe;
use function Fp4\PHP\Module\Str\startsWith;
use function is_int;
use function is_string;

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
            Type\isSameAs('array<string, int>'),
            Assert\same(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );
    }

    #[Test]
    public static function from(): void
    {
        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            Type\isSameAs('array<string, int>'),
            Assert\same(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );
    }

    #[Test]
    public static function fromNonEmpty(): void
    {
        pipe(
            D\fromNonEmpty(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            Type\isSameAs('non-empty-array<string, int>'),
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
            D\map(fn($num) => S\from(['num' => $num])),
            Type\isSameAs('array<string, array{num: int}>'),
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
            D\mapKV(fn($key, $num) => S\from(['num' => $num, 'key' => "k-{$key}"])),
            Type\isSameAs('array<string, array{num: int, key: non-empty-string}>'),
            Assert\same([
                'fst' => ['num' => 1, 'key' => 'k-fst'],
                'snd' => ['num' => 2, 'key' => 'k-snd'],
                'thr' => ['num' => 3, 'key' => 'k-thr'],
            ]),
        );
    }

    #[Test]
    public static function tap(): void
    {
        $expected = (object) ['key-1' => 1, 'key-2' => 2, 'key-3' => 3];
        $toMutate = new stdClass();

        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\tap(fn($num) => $toMutate->{"key-{$num}"} = $num),
            Type\isSameAs('array<string, int>'),
            Assert\equals(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );

        pipe(
            $expected,
            Assert\equals($toMutate),
        );
    }

    #[Test]
    public static function tapKV(): void
    {
        $expected = (object) ['key-fst' => 1, 'key-snd' => 2, 'key-thr' => 3];
        $toMutate = new stdClass();

        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\tapKV(fn($key, $num) => $toMutate->{"key-{$key}"} = $num),
            Type\isSameAs('array<string, int>'),
            Assert\equals(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );

        pipe(
            $expected,
            Assert\equals($toMutate),
        );
    }

    #[Test]
    public static function flatMap(): void
    {
        pipe(
            D\from([]),
            D\flatMap(fn() => D\from([])),
            Type\isSameAs('array<never, never>'),
            Assert\same([]),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3, 'fth' => 4]),
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
            D\flatMapKV(fn() => D\from([])),
            Type\isSameAs('array<never, never>'),
            Assert\same([]),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3, 'fth' => 4]),
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
            D\filter(fn() => true),
            Type\isSameAs('array<never, never>'),
            Assert\same([]),
        );

        pipe(
            D\from([1, 2, 3, 4, 5, 6, 7, 8]),
            D\filter(fn($num) => 0 === $num % 2),
            Type\isSameAs('array<int, int>'),
            Assert\same([1 => 2, 3 => 4, 5 => 6, 7 => 8]),
        );

        pipe(
            D\from(['fst' => 1, 'snd' => '2', 'thr' => 3]),
            Type\isSameAs('array<string, int|string>'),
            D\filter(is_int(...)),
            Type\isSameAs('array<string, int>'),
            Assert\same(['fst' => 1, 'thr' => 3]),
        );
    }

    #[Test]
    public function filterKV(): void
    {
        pipe(
            D\from([]),
            D\filterKV(fn() => true),
            Type\isSameAs('array<never, never>'),
            Assert\same([]),
        );

        pipe(
            D\from([1, 2, 3, 4, 5, 6, 7, 8]),
            D\filterKV(fn($key) => 0 === $key % 2),
            Type\isSameAs('array<int, int>'),
            Assert\same([0 => 1, 2 => 3, 4 => 5, 6 => 7]),
        );

        pipe(
            D\from([1 => 1, 'snd' => 2, 3 => 3]),
            Type\isSameAs('array<int|string, int>'),
            D\filterKV(fn($key, $_) => is_int($key)),
            Type\isSameAs('array<int, int>'),
            Assert\same([1 => 1, 3 => 3]),
        );
    }

    #[Test]
    public static function reindex(): void
    {
        pipe(
            D\from(['k1' => 1, 'k2' => 2, 'k3' => 3]),
            D\reindex(fn(int $num) => "key-{$num}"),
            Type\isSameAs('array<string, int>'),
            Assert\equals(['key-1' => 1, 'key-2' => 2, 'key-3' => 3]),
        );
    }

    #[Test]
    public static function reindexKV(): void
    {
        pipe(
            D\from(['a' => 1, 'b' => 2, 'c' => 3]),
            D\reindexKV(fn(string $key, int $num) => "key-{$key}-{$num}"),
            Type\isSameAs('array<string, int>'),
            Assert\equals(['key-a-1' => 1, 'key-b-2' => 2, 'key-c-3' => 3]),
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
            Type\isSameAs('non-empty-array<string, int>'),
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
            Type\isSameAs('non-empty-array<string, int>'),
            Assert\same(['fst' => 1, 'snd' => 2, 'thr' => 3]),
        );
    }

    // endregion: ops

    // region: terminal ops

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
            Type\isSameAs('Option<int|string>'),
            Assert\equals(O\some(1)),
        );
    }

    #[Test]
    public function keys(): void
    {
        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\keys(...),
            Type\isSameAs('list<string>'),
            Assert\same(['fst', 'snd', 'thr']),
        );
    }

    #[Test]
    public function values(): void
    {
        pipe(
            D\from(['fst' => 1, 'snd' => 2, 'thr' => 3]),
            D\values(...),
            Type\isSameAs('list<int>'),
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
            Type\isSameAs('Option<array<string, int>>'),
            Assert\equals(O\none),
        );

        pipe(
            D\from(['fst' => 2, 'snd' => 4, 'thr' => 6]),
            D\traverseOption($proveEven),
            Type\isSameAs('Option<array<string, int>>'),
            Assert\equals(O\some(['fst' => 2, 'snd' => 4, 'thr' => 6])),
        );
    }

    #[Test]
    public static function any_(): void
    {
        pipe(
            D\from([]),
            D\any(constTrue(...)),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );

        pipe(
            D\from(['k1' => 1, 'k2' => 2, 'k3' => 3, 'k4' => 4]),
            D\any(fn($num) => 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k2' => 3,
                'k3' => 5,
                'k4' => 7,
            ]),
            D\any(fn($num) => 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function anyKV(): void
    {
        pipe(
            D\from([]),
            D\anyKV(constTrue(...)),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k2' => 2,
                'k3' => 3,
                'k4' => 4,
            ]),
            D\anyKV(fn($k, $num) => pipe($k, startsWith('k')) && 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k2' => 3,
                'k3' => 5,
                'k4' => 7,
            ]),
            D\anyKV(fn($k, $num) => pipe($k, startsWith('k')) && 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function all(): void
    {
        pipe(
            D\from([]),
            D\all(constTrue(...)),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            D\from([
                'k1' => 2,
                'k2' => 4,
                'k3' => 6,
                'k4' => 8,
            ]),
            D\all(fn($num) => 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k2' => 2,
                'k3' => 3,
                'k4' => 4,
                'k5' => 5,
                'k6' => 6,
                'k7' => 7,
                'k8' => 8,
            ]),
            D\all(fn($num) => 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function allKV(): void
    {
        pipe(
            D\from([]),
            D\allKV(constTrue(...)),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            D\from([
                'k1' => 2,
                'k2' => 4,
                'k3' => 6,
                'k4' => 8,
            ]),
            D\allKV(fn($k, $num) => pipe($k, startsWith('k')) && 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k2' => 2,
                'k3' => 3,
                'k4' => 4,
                'k5' => 5,
                'k6' => 6,
                'k7' => 7,
                'k8' => 8,
            ]),
            D\allKV(fn($k, $num) => pipe($k, startsWith('k')) && 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function partition(): void
    {
        pipe(
            D\from([]),
            D\partition(fn() => true),
            Type\isSameAs('list{array<never, never>, array<never, never>}'),
            Assert\equals([[], []]),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k2' => 2,
                'k3' => 3,
                'k4' => 4,
                'k5' => 5,
                'k6' => 6,
                'k7' => 7,
                'k8' => 8,
                'k9' => 9,
            ]),
            D\partition(fn($i) => 0 === $i % 2),
            Type\isSameAs('list{array<string, int>, array<string, int>}'),
            Assert\equals([
                [
                    'k1' => 1,
                    'k3' => 3,
                    'k5' => 5,
                    'k7' => 7,
                    'k9' => 9,
                ],
                [
                    'k2' => 2,
                    'k4' => 4,
                    'k6' => 6,
                    'k8' => 8,
                ],
            ]),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k3' => 3,
                'k5' => 5,
                'k7' => 7,
                'k9' => 9,
            ]),
            D\partition(fn($i) => 0 === $i % 2),
            Type\isSameAs('list{array<string, int>, array<string, int>}'),
            Assert\equals([
                [
                    'k1' => 1,
                    'k3' => 3,
                    'k5' => 5,
                    'k7' => 7,
                    'k9' => 9,
                ],
                [],
            ]),
        );

        pipe(
            D\from([
                'k2' => 2,
                'k4' => 4,
                'k6' => 6,
                'k8' => 8,
            ]),
            D\partition(fn($i) => 0 === $i % 2),
            Type\isSameAs('list{array<string, int>, array<string, int>}'),
            Assert\equals([
                [],
                [
                    'k2' => 2,
                    'k4' => 4,
                    'k6' => 6,
                    'k8' => 8,
                ],
            ]),
        );

        pipe(
            D\from([
                'k1' => 'fst',
                'k2' => 1,
                'k3' => 'snd',
                'k4' => 2,
                'k5' => 'thr',
                'k6' => 3,
            ]),
            D\partition(is_string(...)),
            Type\isSameAs('list{array<string, int>, array<string, string>}'),
            Assert\equals([
                [
                    'k2' => 1,
                    'k4' => 2,
                    'k6' => 3,
                ],
                [
                    'k1' => 'fst',
                    'k3' => 'snd',
                    'k5' => 'thr',
                ],
            ]),
        );
    }

    #[Test]
    public static function partitionMap(): void
    {
        pipe(
            D\from([]),
            D\partitionMap(fn($i) => E\right($i)),
            Type\isSameAs('list{array<never, never>, array<never, never>}'),
            Assert\equals([[], []]),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k2' => 2,
                'k3' => 3,
                'k4' => 4,
                'k5' => 5,
                'k6' => 6,
                'k7' => 7,
                'k8' => 8,
                'k9' => 9,
            ]),
            D\partitionMap(fn($i) => 0 !== $i % 2 ? E\left($i) : E\right($i)),
            Type\isSameAs('list{array<string, int>, array<string, int>}'),
            Assert\equals([
                [
                    'k1' => 1,
                    'k3' => 3,
                    'k5' => 5,
                    'k7' => 7,
                    'k9' => 9,
                ],
                [
                    'k2' => 2,
                    'k4' => 4,
                    'k6' => 6,
                    'k8' => 8,
                ],
            ]),
        );

        pipe(
            D\from([
                'k1' => 1,
                'k2' => 3,
                'k3' => 5,
                'k4' => 7,
                'k5' => 9,
            ]),
            D\partitionMap(fn($i) => 0 !== $i % 2 ? E\left($i) : E\right($i)),
            Type\isSameAs('list{array<string, int>, array<string, int>}'),
            Assert\equals([
                [
                    'k1' => 1,
                    'k2' => 3,
                    'k3' => 5,
                    'k4' => 7,
                    'k5' => 9,
                ],
                [],
            ]),
        );

        pipe(
            D\from([
                'k1' => 2,
                'k2' => 4,
                'k3' => 6,
                'k4' => 8,
            ]),
            D\partitionMap(fn($i) => 0 !== $i % 2 ? E\left($i) : E\right($i)),
            Type\isSameAs('list{array<string, int>, array<string, int>}'),
            Assert\equals([
                [],
                [
                    'k1' => 2,
                    'k2' => 4,
                    'k3' => 6,
                    'k4' => 8,
                ],
            ]),
        );

        pipe(
            D\from([
                'k1' => 'fst',
                'k2' => 1,
                'k3' => 'snd',
                'k4' => 2,
                'k5' => 'thr',
                'k6' => 3,
            ]),
            D\partitionMap(fn($i) => is_int($i) ? E\left($i) : E\right($i)),
            Type\isSameAs('list{array<string, int>, array<string, string>}'),
            Assert\equals([
                [
                    'k2' => 1,
                    'k4' => 2,
                    'k6' => 3,
                ],
                [
                    'k1' => 'fst',
                    'k3' => 'snd',
                    'k5' => 'thr',
                ],
            ]),
        );
    }

    // endregion: terminal ops
}
