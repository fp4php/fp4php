<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use ArrayObject;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Psalm as Type;
use Fp4\PHP\Type\Option;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Fp4\PHP\Module\Functions\constTrue;
use function Fp4\PHP\Module\Functions\pipe;

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
            Type\isSameAs('non-empty-list<int>'),
            Assert\equals([1, 2, 3]),
        );
    }

    #[Test]
    public static function fromLiteral(): void
    {
        pipe(
            L\fromLiteral([1, 2, 3]),
            Type\isSameAs('list{1, 2, 3}'),
            Assert\equals([1, 2, 3]),
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
            Type\isSameAs('non-empty-list<int>'),
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
            Type\isSameAs('non-empty-list<int>'),
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
        $joinWithIndex = fn(int $index, string $offset): string => "{$index}-{$offset}";

        pipe(
            L\from([]),
            L\mapKV($joinWithIndex),
            Type\isSameAs('list<non-empty-string>'),
            Assert\equals([]),
        );

        pipe(
            L\from(['fst', 'snd', 'thr']),
            L\mapKV($joinWithIndex),
            Type\isSameAs('non-empty-list<non-empty-string>'),
            Assert\equals(['0-fst', '1-snd', '2-thr']),
        );
    }

    #[Test]
    public static function flatMap(): void
    {
        $getEmptyList = fn(int $_i): array => /** @var list<int> */ [];
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
            Type\isSameAs('non-empty-list<int>'),
            Assert\equals([0, 1, 2, 1, 2, 3, 2, 3, 4]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\flatMap($getEmptyList),
            Type\isSameAs('list<int>'),
            Assert\equals([]),
        );
    }

    #[Test]
    public static function flatMapKV(): void
    {
        $getEmptyList = fn(int $_position, int $_i): array => /** @var list<non-empty-string> */ [];
        $getSiblingsAtPosition = fn(int $position, int $i): array => [
            sprintf('%s:[%s]', $position, $i - 1),
            sprintf('%s:[%s]', $position, $i),
            sprintf('%s:[%s]', $position, $i + 1),
        ];

        pipe(
            L\from([]),
            L\flatMapKV($getSiblingsAtPosition),
            Type\isSameAs('list<non-empty-string>'),
            Assert\equals([]),
        );

        pipe(
            L\from([1, 2, 3]),
            L\flatMapKV($getSiblingsAtPosition),
            Type\isSameAs('non-empty-list<non-empty-string>'),
            Assert\equals(['0:[0]', '0:[1]', '0:[2]', '1:[1]', '1:[2]', '1:[3]', '2:[2]', '2:[3]', '2:[4]']),
        );

        pipe(
            L\from([1, 2, 3]),
            L\flatMapKV($getEmptyList),
            Type\isSameAs('list<non-empty-string>'),
            Assert\equals([]),
        );
    }

    #[Test]
    public static function filter(): void
    {
        pipe(
            L\from([]),
            L\filter(fn(int $num) => 0 !== $num % 2),
            Type\isSameAs('list<int>'),
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

    // endregion: ops

    // region: terminal ops

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
            L\from([1, 2, 3]),
            L\traverseOption($proveEven),
            Type\isSameAs('Option<non-empty-list<int>>'),
            Assert\equals(O\none),
        );

        pipe(
            L\from([2, 4, 6]),
            L\traverseOption($proveEven),
            Type\isSameAs('Option<non-empty-list<int>>'),
            Assert\equals(O\some([2, 4, 6])),
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
            L\map(fn($i) => [$i->a, $i->b]),
            Type\isSameAs('list<array{non-empty-string, non-empty-string}>'),
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
                c: fn($_) => 'c1',
                d: fn($_) => 'd1',
            ),
            L\map(fn($i) => [$i->a, $i->b, $i->c, $i->d]),
            Type\isSameAs('list<array{non-empty-string, non-empty-string, "c1", "d1"}>'),
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
