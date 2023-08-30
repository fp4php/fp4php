<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use ArrayObject;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Either as E;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Psalm as Type;
use Fp4\PHP\Module\Str;
use Fp4\PHP\Module\Tuple as T;
use Fp4\PHP\Type\Either;
use Fp4\PHP\Type\Option;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Fp4\PHP\Module\Combinator\constTrue;
use function Fp4\PHP\Module\Combinator\pipe;
use function is_int;
use function is_string;

/**
 * @api
 */
final class ArrayListTest extends TestCase
{
    // region: constructor

    #[Test]
    public static function fromIterable(): void
    {
        pipe(
            L\fromIterable(new ArrayObject([1, 2, 3])),
            Type\isSameAs('list<int>'),
            Assert\equals([1, 2, 3]),
        );
    }

    #[Test]
    public static function from(): void
    {
        pipe(
            L\from([1, 2, 3]),
            Type\isSameAs('list<int>'),
            Assert\equals([1, 2, 3]),
        );
    }

    #[Test]
    public static function fromNonEmpty(): void
    {
        pipe(
            L\fromNonEmpty([1, 2, 3]),
            Type\isSameAs('non-empty-list<int>'),
            Assert\equals([1, 2, 3]),
        );
    }

    #[Test]
    public static function singleton(): void
    {
        pipe(
            L\singleton(42),
            Type\isSameAs('non-empty-list<int>'),
            Assert\equals([42]),
        );
    }

    // endregion: constructor

    // region: ops

    #[Test]
    public static function map(): void
    {
        $addOne = fn(int $i): int => $i + 1;

        pipe(
            L\from([]),
            L\map($addOne),
            Type\isSameAs('list<int>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\map($addOne),
            Type\isSameAs('list<int>'),
            Assert\equals([2, 3, 4]),
        );
    }

    #[Test]
    public static function tap(): void
    {
        $expected = (object) ['key-1' => 1, 'key-2' => 2, 'key-3' => 3];
        $toMutate = new stdClass();

        pipe(
            L\from([1, 2, 3]),
            L\tap(fn($num) => $toMutate->{"key-{$num}"} = $num),
            Type\isSameAs('list<int>'),
            Assert\equals([1, 2, 3]),
        );

        pipe(
            $expected,
            Assert\equals($toMutate),
        );
    }

    #[Test]
    public static function tapKV(): void
    {
        $expected = (object) ['key-0' => 1, 'key-1' => 2, 'key-2' => 3];
        $toMutate = new stdClass();

        pipe(
            L\from([1, 2, 3]),
            L\tapKV(fn($key, $num) => $toMutate->{"key-{$key}"} = $num),
            Type\isSameAs('list<int>'),
            Assert\equals([1, 2, 3]),
        );

        pipe(
            $expected,
            Assert\equals($toMutate),
        );
    }

    #[Test]
    public static function mapKV(): void
    {
        $joinWithIndex = fn(int $index, string $offset): string => Str\from("{$index}-{$offset}");

        pipe(
            L\from([]),
            L\mapKV($joinWithIndex),
            Type\isSameAs('list<string>'),
            Assert\equals([]),
        );

        pipe(
            L\from(['fst', 'snd', 'thr']),
            L\mapKV($joinWithIndex),
            Type\isSameAs('list<string>'),
            Assert\equals(['0-fst', '1-snd', '2-thr']),
        );
    }

    #[Test]
    public static function flatMap(): void
    {
        $getSiblings = fn(int $i): array => [$i - 1, $i, $i + 1];

        pipe(
            L\from([]),
            L\flatMap($getSiblings),
            Type\isSameAs('list<int>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\flatMap($getSiblings),
            Type\isSameAs('list<int>'),
            Assert\equals([0, 1, 2, 1, 2, 3, 2, 3, 4]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\flatMap(fn() => []),
            Type\isSameAs('list<never>'),
            Assert\equals([]),
        );
    }

    #[Test]
    public static function flatMapKV(): void
    {
        $getSiblingsAtPosition = fn(int $position, int $i): array => [
            Str\from(sprintf('%s:[%s]', $position, $i - 1)),
            Str\from(sprintf('%s:[%s]', $position, $i)),
            Str\from(sprintf('%s:[%s]', $position, $i + 1)),
        ];

        pipe(
            L\from([]),
            L\flatMapKV($getSiblingsAtPosition),
            Type\isSameAs('list<string>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\flatMapKV($getSiblingsAtPosition),
            Type\isSameAs('list<string>'),
            Assert\equals(['0:[0]', '0:[1]', '0:[2]', '1:[1]', '1:[2]', '1:[3]', '2:[2]', '2:[3]', '2:[4]']),
        );

        pipe(
            L\from([1, 2, 3]),
            L\flatMapKV(fn() => []),
            Type\isSameAs('list<never>'),
            Assert\equals([]),
        );
    }

    #[Test]
    public static function filter(): void
    {
        pipe(
            L\from([]),
            L\filter(fn(int $num) => 0 !== $num % 2),
            Type\isSameAs('list<never>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\filter(fn($num) => 0 === $num % 2),
            Type\isSameAs('list<int>'),
            Assert\equals([2, 4, 6, 8]),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\filter(fn($num) => 0 !== $num % 2),
            Type\isSameAs('list<int>'),
            Assert\equals([1, 3, 5, 7]),
        );

        pipe(
            L\from([1, 'fst', 2, 'snd', 3, 'thr']),
            L\filter(is_int(...)),
            Type\isSameAs('list<int>'),
            Assert\equals([1, 2, 3]),
        );
    }

    #[Test]
    public static function filterKV(): void
    {
        pipe(
            L\from([]),
            L\filterKV(fn($_key, $_value) => true),
            Type\isSameAs('list<never>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\filterKV(fn($key, $num) => (0 === $num % 2) && (1 !== $key)),
            Type\isSameAs('list<int>'),
            Assert\equals([4, 6, 8]),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\filterKV(fn($key, $num) => (0 !== $num % 2) && (0 !== $key)),
            Type\isSameAs('list<int>'),
            Assert\equals([3, 5, 7]),
        );

        pipe(
            L\from([1, 'fst', 2, 'snd', 3, 'thr']),
            L\filterKV(fn($key, $value) => is_int($value) && 0 !== $key),
            Type\isSameAs('list<int>'),
            Assert\equals([2, 3]),
        );
    }

    #[Test]
    public static function prepend(): void
    {
        pipe(
            L\from([]),
            L\prepend(42),
            Type\isSameAs('non-empty-list<42>'),
            Assert\equals([42]),
        );

        pipe(
            L\from([43, 44]),
            L\prepend(42),
            Type\isSameAs('non-empty-list<int>'),
            Assert\equals([42, 43, 44]),
        );
    }

    #[Test]
    public static function append(): void
    {
        pipe(
            L\from([]),
            L\append(42),
            Type\isSameAs('non-empty-list<42>'),
            Assert\equals([42]),
        );

        pipe(
            L\from([40, 41]),
            L\append(42),
            Type\isSameAs('non-empty-list<int>'),
            Assert\equals([40, 41, 42]),
        );
    }

    #[Test]
    public static function tail(): void
    {
        pipe(
            L\from([]),
            L\tail(...),
            Type\isSameAs('list<never>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\tail(...),
            Type\isSameAs('list<int>'),
            Assert\equals([2, 3]),
        );
    }

    // endregion: ops

    // region: terminal ops

    #[Test]
    public static function get(): void
    {
        pipe(
            L\from([]),
            L\get(0),
            Type\isSameAs('Option<never>'),
            Assert\same(O\none),
        );

        pipe(
            L\from([1, 2]),
            L\get(0),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(1)),
        );

        pipe(
            L\from([1, 2]),
            L\get(1),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(2)),
        );

        pipe(
            L\from([1, 2]),
            L\get(2),
            Type\isSameAs('Option<int>'),
            Assert\same(O\none),
        );
    }

    #[Test]
    public static function contains(): void
    {
        pipe(
            L\from([]),
            L\contains(42),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );

        pipe(
            L\from([1, 2, 3]),
            L\contains(42),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );

        pipe(
            L\from([40, 41, 42]),
            L\contains(42),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );
    }

    #[Test]
    public static function first(): void
    {
        pipe(
            L\from([]),
            L\first(...),
            Assert\equals(O\none),
        );

        pipe(
            L\from([1]),
            L\first(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(1)),
        );

        pipe(
            L\from([1, 2, 3]),
            L\first(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(1)),
        );
    }

    #[Test]
    public static function second(): void
    {
        pipe(
            L\from([]),
            L\second(...),
            Assert\equals(O\none),
        );

        pipe(
            L\from([1]),
            L\second(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\none),
        );

        pipe(
            L\from([1, 2]),
            L\second(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(2)),
        );

        pipe(
            L\from([1, 2, 3]),
            L\second(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(2)),
        );
    }

    #[Test]
    public static function third(): void
    {
        pipe(
            L\from([]),
            L\third(...),
            Assert\equals(O\none),
        );

        pipe(
            L\from([1]),
            L\third(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\none),
        );

        pipe(
            L\from([1, 2]),
            L\third(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\none),
        );

        pipe(
            L\from([1, 2, 3]),
            L\third(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(3)),
        );

        pipe(
            L\from([1, 2, 3, 4]),
            L\third(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(3)),
        );
    }

    #[Test]
    public static function last(): void
    {
        pipe(
            L\from([]),
            L\last(...),
            Assert\equals(O\none),
        );

        pipe(
            L\from([1]),
            L\last(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(1)),
        );

        pipe(
            L\from([1, 2, 3]),
            L\last(...),
            Type\isSameAs('Option<int>'),
            Assert\equals(O\some(3)),
        );
    }

    #[Test]
    public static function traverseOption(): void
    {
        $proveEven = fn(int $i): Option => 0 === $i % 2
            ? O\some($i)
            : O\none;

        pipe(
            L\from([]),
            L\traverseOptionKV($proveEven),
            Type\isSameAs('Option<list<int>>'),
            Assert\equals(O\some([])),
        );

        pipe(
            L\from([1, 2, 3]),
            L\traverseOption($proveEven),
            Type\isSameAs('Option<list<int>>'),
            Assert\equals(O\none),
        );

        pipe(
            L\from([2, 4, 6]),
            L\traverseOption($proveEven),
            Type\isSameAs('Option<list<int>>'),
            Assert\equals(O\some([2, 4, 6])),
        );
    }

    #[Test]
    public static function traverseOptionKV(): void
    {
        $proveEvenOrLiteral = fn(int $k, int $v): Option => 0 === $v % 2 || 0 === $k
            ? O\some($v)
            : O\none;

        pipe(
            L\from([]),
            L\traverseOptionKV($proveEvenOrLiteral),
            Type\isSameAs('Option<list<int>>'),
            Assert\equals(O\some([])),
        );

        pipe(
            L\from([1, 2, 3]),
            L\traverseOptionKV($proveEvenOrLiteral),
            Type\isSameAs('Option<list<int>>'),
            Assert\equals(O\none),
        );

        pipe(
            L\from([1, 2, 4, 6]),
            L\traverseOptionKV($proveEvenOrLiteral),
            Type\isSameAs('Option<list<int>>'),
            Assert\equals(O\some([1, 2, 4, 6])),
        );
    }

    #[Test]
    public static function traverseEither(): void
    {
        $proveEven = fn(int $i): Either => 0 !== $i % 2
            ? E\left(Str\from("{$i} is not even"))
            : E\right($i);

        pipe(
            L\from([]),
            L\traverseEitherKV($proveEven),
            Type\isSameAs('Either<string, list<int>>'),
            Assert\equals(E\right([])),
        );

        pipe(
            L\from([1, 2, 3]),
            L\traverseEitherKV($proveEven),
            Type\isSameAs('Either<string, list<int>>'),
            Assert\equals(E\left('1 is not even')),
        );

        pipe(
            L\from([2, 4, 6]),
            L\traverseEither($proveEven),
            Type\isSameAs('Either<string, list<int>>'),
            Assert\equals(E\right([2, 4, 6])),
        );
    }

    #[Test]
    public static function traverseEitherKV(): void
    {
        $proveEvenOrLiteral = fn(int $k, int $v): Either => 0 !== $v % 2 && 0 !== $k
            ? E\left(Str\from("{$v} is not even and key is not 0"))
            : E\right($v);

        pipe(
            L\from([]),
            L\traverseEitherKV($proveEvenOrLiteral),
            Type\isSameAs('Either<string, list<int>>'),
            Assert\equals(E\right([])),
        );

        pipe(
            L\from([1, 2, 3]),
            L\traverseEitherKV($proveEvenOrLiteral),
            Type\isSameAs('Either<string, list<int>>'),
            Assert\equals(E\left('3 is not even and key is not 0')),
        );

        pipe(
            L\from([1, 2, 4, 6]),
            L\traverseEitherKV($proveEvenOrLiteral),
            Type\isSameAs('Either<string, list<int>>'),
            Assert\equals(E\right([1, 2, 4, 6])),
        );
    }

    #[Test]
    public static function any_(): void
    {
        pipe(
            L\from([]),
            L\any(constTrue(...)),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );

        pipe(
            L\from([1, 2, 3, 4]),
            L\any(fn($num) => 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            L\from([1, 3, 5, 7]),
            L\any(fn($num) => 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function anyKV(): void
    {
        pipe(
            L\from([]),
            L\anyKV(constTrue(...)),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );

        pipe(
            L\from([1, 2, 3, 4]),
            L\anyKV(fn($key, $value) => 0 === $key % 2 || 0 === $value % 2),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            L\from([1, 3, 5, 7]),
            L\anyKV(fn($key, $value) => 0 === $key % 2 && 0 === $value % 2),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function all(): void
    {
        pipe(
            L\from([]),
            L\all(constTrue(...)),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            L\from([2, 4, 6, 8]),
            L\all(fn($num) => 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\all(fn($num) => 0 === $num % 2),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function allKV(): void
    {
        pipe(
            L\from([]),
            L\allKV(constTrue(...)),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\allKV(fn($key, $value) => 0 === $key % 2 || 0 === $value % 2),
            Type\isSameAs('bool'),
            Assert\equals(true),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\allKV(fn($key, $value) => 0 === $key % 2 && 0 === $value % 2),
            Type\isSameAs('bool'),
            Assert\equals(false),
        );
    }

    #[Test]
    public static function reindex(): void
    {
        pipe(
            L\from([]),
            L\reindex(fn(int $num) => Str\from('key'.$num)),
            Type\isSameAs('array<string, never>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\reindex(fn(int $num) => Str\from("key-{$num}")),
            Type\isSameAs('array<string, int>'),
            Assert\equals(['key-1' => 1, 'key-2' => 2, 'key-3' => 3]),
        );
    }

    #[Test]
    public static function reindexKV(): void
    {
        pipe(
            L\from([]),
            L\reindexKV(fn(int $key, int $value) => Str\from('key-'.($key + $value))),
            Type\isSameAs('array<string, never>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\reindexKV(fn(int $key, int $value) => Str\from('key-'.($key + $value))),
            Type\isSameAs('array<string, int>'),
            Assert\equals(['key-1' => 1, 'key-3' => 2, 'key-5' => 3]),
        );
    }

    #[Test]
    public static function partition(): void
    {
        pipe(
            L\from([]),
            L\partition(fn() => true),
            Type\isSameAs('array{list<never>, list<never>}'),
            Assert\equals([[], []]),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8, 9]),
            L\partition(fn($i) => 0 === $i % 2),
            Type\isSameAs('array{list<int>, list<int>}'),
            Assert\equals([[1, 3, 5, 7, 9], [2, 4, 6, 8]]),
        );

        pipe(
            L\from([1, 3, 5, 7, 9]),
            L\partition(fn($i) => 0 === $i % 2),
            Type\isSameAs('array{list<int>, list<int>}'),
            Assert\equals([[1, 3, 5, 7, 9], []]),
        );

        pipe(
            L\from([2, 4, 6, 8]),
            L\partition(fn($i) => 0 === $i % 2),
            Type\isSameAs('array{list<int>, list<int>}'),
            Assert\equals([[], [2, 4, 6, 8]]),
        );

        pipe(
            L\from(['fst', 1, 'snd', 2, 'thr', 3]),
            L\partition(fn($i) => is_string($i)),
            Type\isSameAs('array{list<int>, list<string>}'),
            Assert\equals([[1, 2, 3], ['fst', 'snd', 'thr']]),
        );
    }

    #[Test]
    public static function partitionKV(): void
    {
        pipe(
            L\from([]),
            L\partitionKV(fn() => true),
            Type\isSameAs('array{list<never>, list<never>}'),
            Assert\equals([[], []]),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8, 9]),
            L\partitionKV(fn($k, $v) => 0 === $v % 2 || $k === 0),
            Type\isSameAs('array{list<int>, list<int>}'),
            Assert\equals([[3, 5, 7, 9], [1, 2, 4, 6, 8]]),
        );
    }

    #[Test]
    public static function partitionMap(): void
    {
        pipe(
            L\from([]),
            L\partitionMap(fn($i) => E\right($i)),
            Type\isSameAs('array{list<never>, list<never>}'),
            Assert\equals([[], []]),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8, 9]),
            L\partitionMap(fn($i) => 0 !== $i % 2 ? E\left($i) : E\right($i)),
            Type\isSameAs('array{list<int>, list<int>}'),
            Assert\equals([[1, 3, 5, 7, 9], [2, 4, 6, 8]]),
        );

        pipe(
            L\from([1, 3, 5, 7, 9]),
            L\partitionMap(fn($i) => 0 !== $i % 2 ? E\left($i) : E\right($i)),
            Type\isSameAs('array{list<int>, list<int>}'),
            Assert\equals([[1, 3, 5, 7, 9], []]),
        );

        pipe(
            L\from([2, 4, 6, 8]),
            L\partitionMap(fn($i) => 0 !== $i % 2 ? E\left($i) : E\right($i)),
            Type\isSameAs('array{list<int>, list<int>}'),
            Assert\equals([[], [2, 4, 6, 8]]),
        );

        pipe(
            L\from(['fst', 1, 'snd', 2, 'thr', 3]),
            L\partitionMap(fn($i) => is_int($i) ? E\left($i) : E\right($i)),
            Type\isSameAs('array{list<int>, list<string>}'),
            Assert\equals([[1, 2, 3], ['fst', 'snd', 'thr']]),
        );
    }

    #[Test]
    public static function partitionMapKV(): void
    {
        pipe(
            L\from([]),
            L\partitionMapKV(fn($_, $v) => E\right($v)),
            Type\isSameAs('array{list<never>, list<never>}'),
            Assert\equals([[], []]),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8, 9]),
            L\partitionMapKV(fn($k, $v) => 0 !== $v % 2 && $k !== 0 ? E\left($v) : E\right($v)),
            Type\isSameAs('array{list<int>, list<int>}'),
            Assert\equals([[3, 5, 7, 9], [1, 2, 4, 6, 8]]),
        );
    }

    #[Test]
    public static function fold(): void
    {
        pipe(
            L\from([1, 2, 3]),
            L\fold(0, fn($sum, $current) => $sum + $current),
            Type\isSameAs('int'),
            Assert\same(6),
        );

        pipe(
            L\from([1, 2, 3]),
            L\fold([], fn($mapped, $current) => [...$mapped, "value-{$current}"]),
            Type\isSameAs('list<non-empty-string>'),
            Assert\same(['value-1', 'value-2', 'value-3']),
        );
    }

    #[Test]
    public static function foldKV(): void
    {
        pipe(
            L\from([1, 2, 3, 4, 5, 6]),
            L\foldKV(0, fn($sum, $key, $current) => $sum + (0 === $key % 2 ? $current : 0)),
            Type\isSameAs('int'),
            Assert\same(1 + 3 + 5),
        );

        pipe(
            L\from([1, 2, 3, 4, 5, 6]),
            L\foldKV([], fn($mapped, $key, $current) => 0 === $key % 2 ? [...$mapped, "value-{$current}"] : $mapped),
            Type\isSameAs('list<non-empty-string>'),
            Assert\same(['value-1', 'value-3', 'value-5']),
        );
    }

    // endregion: terminal ops

    // region: bindable

    #[Test]
    public static function bind(): void
    {
        pipe(
            L\bindable(),
            L\bind(
                a: fn() => L\from(['a1', 'a2', 'a3']),
                b: fn() => L\from(['b1', 'b2']),
            ),
            L\map(fn($i) => T\from([$i->a, $i->b])),
            Type\isSameAs('list<array{string, string}>'),
            Assert\same([
                ['a1', 'b1'],
                ['a1', 'b2'],
                ['a2', 'b1'],
                ['a2', 'b2'],
                ['a3', 'b1'],
                ['a3', 'b2'],
            ]),
        );
    }

    #[Test]
    public static function let(): void
    {
        pipe(
            L\bindable(),
            L\let(a: fn() => 'a1', b: fn() => 'b1'),
            L\map(fn($i) => [$i->a, $i->b]),
            Type\isSameAs('list<array{"a1", "b1"}>'),
            Assert\same([
                ['a1', 'b1'],
            ]),
        );

        pipe(
            L\bindable(),
            L\bind(
                a: fn() => L\from(['a1', 'a2', 'a3']),
                b: fn() => L\from(['b1', 'b2']),
            ),
            L\let(
                c: fn() => Str\from('c1'),
                d: fn() => Str\from('d1'),
            ),
            L\map(fn($i) => T\from([$i->a, $i->b, $i->c, $i->d])),
            Type\isSameAs('list<array{string, string, string, string}>'),
            Assert\same([
                ['a1', 'b1', 'c1', 'd1'],
                ['a1', 'b2', 'c1', 'd1'],
                ['a2', 'b1', 'c1', 'd1'],
                ['a2', 'b2', 'c1', 'd1'],
                ['a3', 'b1', 'c1', 'd1'],
                ['a3', 'b2', 'c1', 'd1'],
            ]),
        );
    }

    // endregion: bindable
}
