<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use ArrayObject;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Psalm as PsalmType;
use Fp4\PHP\Type\Option;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function Fp4\PHP\Module\Functions\constTrue;
use function Fp4\PHP\Module\Functions\pipe;
use function PHPUnit\Framework\assertEquals;

/**
 * @api
 */
final class ArrayListTest extends TestCase
{
    // region: constructor

    #[Test]
    public static function fromIterable(): void
    {
        assertEquals([1, 2, 3], pipe(
            L\fromIterable(new ArrayObject([1, 2, 3])),
            PsalmType\isSameAs('list<int>'),
        ));
    }

    #[Test]
    public static function from(): void
    {
        assertEquals([1, 2, 3], pipe(
            L\from([1, 2, 3]),
            PsalmType\isSameAs('non-empty-list<int>'),
        ));
    }

    #[Test]
    public static function fromLiteral(): void
    {
        assertEquals([1, 2, 3], pipe(
            L\fromLiteral([1, 2, 3]),
            PsalmType\isSameAs('list{1, 2, 3}'),
        ));
    }

    // endregion: constructor

    // region: ops

    #[Test]
    public static function map(): void
    {
        $addOne = fn(int $i): int => $i + 1;

        assertEquals([], pipe(
            L\from([]),
            L\map($addOne),
        ));

        assertEquals([2, 3, 4], pipe(
            L\from([1, 2, 3]),
            L\map($addOne),
        ));
    }

    #[Test]
    public static function tap(): void
    {
        $expected = (object) ['key-1' => 1, 'key-2' => 2, 'key-3' => 3];
        $toMutate = new stdClass();

        assertEquals([1, 2, 3], pipe(
            L\from([1, 2, 3]),
            L\tap(fn($num) => $toMutate->{"key-{$num}"} = $num),
        ));

        assertEquals($expected, $toMutate);
    }

    #[Test]
    public static function mapKV(): void
    {
        $joinWithIndex = fn(int $index, string $offset): string => "{$index}-{$offset}";

        assertEquals([], pipe(
            L\from([]),
            L\mapKV($joinWithIndex),
        ));

        assertEquals(['0-fst', '1-snd', '2-thr'], pipe(
            L\from(['fst', 'snd', 'thr']),
            L\mapKV($joinWithIndex),
        ));
    }

    #[Test]
    public static function flatMap(): void
    {
        $getSiblings = fn(int $i): array => [$i - 1, $i, $i + 1];

        assertEquals([], pipe(
            L\from([]),
            L\flatMap($getSiblings),
        ));

        assertEquals([0, 1, 2, 1, 2, 3, 2, 3, 4], pipe(
            L\from([1, 2, 3]),
            L\flatMap($getSiblings),
        ));
    }

    #[Test]
    public static function flatMapKV(): void
    {
        $getSiblingsAtPosition = fn(int $position, int $i): array => [
            sprintf('%s:[%s]', $position, $i - 1),
            sprintf('%s:[%s]', $position, $i),
            sprintf('%s:[%s]', $position, $i + 1),
        ];

        assertEquals([], pipe(
            L\from([]),
            L\flatMapKV($getSiblingsAtPosition),
        ));

        assertEquals(['0:[0]', '0:[1]', '0:[2]', '1:[1]', '1:[2]', '1:[3]', '2:[2]', '2:[3]', '2:[4]'], pipe(
            L\from([1, 2, 3]),
            L\flatMapKV($getSiblingsAtPosition),
        ));
    }

    #[Test]
    public static function filter(): void
    {
        assertEquals([], pipe(
            L\from([]),
            L\filter(fn(int $num) => 0 !== $num % 2),
        ));

        assertEquals([2, 4, 6, 8], pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\filter(fn($num) => 0 === $num % 2),
        ));

        assertEquals([1, 3, 5, 7], pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\filter(fn($num) => 0 !== $num % 2),
        ));

        assertEquals([1, 2, 3], pipe(
            L\from([1, 'fst', 2, 'snd', 3, 'thr']),
            PsalmType\isSameAs('non-empty-list<int|non-empty-string>'),
            L\filter(is_int(...)),
            PsalmType\isSameAs('list<int>'),
        ));
    }

    #[Test]
    public static function prepend(): void
    {
        assertEquals([42], pipe(
            L\from([]),
            L\prepend(42),
        ));

        assertEquals([42, 43, 44], pipe(
            L\from([43, 44]),
            L\prepend(42),
        ));
    }

    #[Test]
    public static function append(): void
    {
        assertEquals([42], pipe(
            L\from([]),
            L\append(42),
        ));

        assertEquals([40, 41, 42], pipe(
            L\from([40, 41]),
            L\append(42),
        ));
    }

    // endregion: ops

    // region: terminal ops

    #[Test]
    public static function contains(): void
    {
        assertEquals(false, pipe(
            L\from([]),
            L\contains(42),
        ));

        assertEquals(false, pipe(
            L\from([1, 2, 3]),
            L\contains(42),
        ));

        assertEquals(true, pipe(
            L\from([40, 41, 42]),
            L\contains(42),
        ));
    }

    #[Test]
    public static function first(): void
    {
        assertEquals(O\none, pipe(
            L\from([]),
            L\first(...),
        ));

        assertEquals(O\some(1), pipe(
            L\from([1]),
            L\first(...),
        ));

        assertEquals(O\some(1), pipe(
            L\from([1, 2, 3]),
            L\first(...),
        ));
    }

    #[Test]
    public static function second(): void
    {
        assertEquals(O\none, pipe(
            L\from([]),
            L\second(...),
        ));

        assertEquals(O\none, pipe(
            L\from([1]),
            L\second(...),
        ));

        assertEquals(O\some(2), pipe(
            L\from([1, 2]),
            L\second(...),
        ));

        assertEquals(O\some(2), pipe(
            L\from([1, 2, 3]),
            L\second(...),
        ));
    }

    #[Test]
    public static function third(): void
    {
        assertEquals(O\none, pipe(
            L\from([]),
            L\third(...),
        ));

        assertEquals(O\none, pipe(
            L\from([1]),
            L\third(...),
        ));

        assertEquals(O\none, pipe(
            L\from([1, 2]),
            L\third(...),
        ));

        assertEquals(O\some(3), pipe(
            L\from([1, 2, 3]),
            L\third(...),
        ));

        assertEquals(O\some(3), pipe(
            L\from([1, 2, 3, 4]),
            L\third(...),
        ));
    }

    #[Test]
    public static function last(): void
    {
        assertEquals(O\none, pipe(
            L\from([]),
            L\last(...),
        ));

        assertEquals(O\some(1), pipe(
            L\from([1]),
            L\last(...),
        ));

        assertEquals(O\some(3), pipe(
            L\from([1, 2, 3]),
            L\last(...),
        ));
    }

    #[Test]
    public static function traverseOption(): void
    {
        $proveEven = fn(int $i): Option => 0 === $i % 2
            ? O\some($i)
            : O\none;

        assertEquals(O\none, pipe(
            L\from([1, 2, 3]),
            L\traverseOption($proveEven),
        ));

        assertEquals(O\some([2, 4, 6]), pipe(
            L\from([2, 4, 6]),
            L\traverseOption($proveEven),
        ));
    }

    #[Test]
    public static function any_(): void
    {
        assertEquals(false, pipe(
            L\from([]),
            L\any(constTrue(...)),
        ));

        assertEquals(true, pipe(
            L\from([1, 2, 3, 4]),
            L\any(fn($num) => 0 === $num % 2),
        ));

        assertEquals(false, pipe(
            L\from([1, 3, 5, 7]),
            L\any(fn($num) => 0 === $num % 2),
        ));
    }

    #[Test]
    public static function all(): void
    {
        assertEquals(true, pipe(
            L\from([]),
            L\all(constTrue(...)),
        ));

        assertEquals(true, pipe(
            L\from([2, 4, 6, 8]),
            L\all(fn($num) => 0 === $num % 2),
        ));

        assertEquals(false, pipe(
            L\from([1, 2, 3, 4, 5, 6, 7, 8]),
            L\all(fn($num) => 0 === $num % 2),
        ));
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
            PsalmType\isSameAs('list<array{non-empty-string, non-empty-string}>'),
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
            PsalmType\isSameAs('list<array{"a1", "b1"}>'),
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
            PsalmType\isSameAs('list<array{non-empty-string, non-empty-string, "c1", "d1"}>'),
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
