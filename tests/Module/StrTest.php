<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Psalm as Type;
use Fp4\PHP\Module\Str;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Module\Combinator\pipe;

final class StrTest extends TestCase
{
    #[Test]
    public static function from(): void
    {
        pipe(
            Str\from('str'),
            Type\isSameAs('string'),
            Assert\same('str'),
        );
    }

    #[Test]
    public static function fromNonEmpty(): void
    {
        pipe(
            Str\fromNonEmpty('str'),
            Type\isSameAs('non-empty-string'),
            Assert\same('str'),
        );
    }

    #[Test]
    public static function append(): void
    {
        pipe(
            Str\from('val'),
            Str\append('-suffix'),
            Type\isSameAs('non-empty-string'),
            Assert\same('val-suffix'),
        );
    }

    #[Test]
    public static function prepend(): void
    {
        pipe(
            Str\from('val'),
            Str\prepend('pref-'),
            Type\isSameAs('non-empty-string'),
            Assert\same('pref-val'),
        );
    }

    #[Test]
    public static function startsWith(): void
    {
        pipe(
            Str\from('val'),
            Str\startsWith('v'),
            Type\isSameAs('bool'),
            Assert\same(true),
        );

        pipe(
            Str\from('val'),
            Str\startsWith('a'),
            Type\isSameAs('bool'),
            Assert\same(false),
        );
    }

    #[Test]
    public static function endsWith(): void
    {
        pipe(
            Str\from('val'),
            Str\endsWith('l'),
            Type\isSameAs('bool'),
            Assert\same(true),
        );

        pipe(
            Str\from('val'),
            Str\startsWith('a'),
            Type\isSameAs('bool'),
            Assert\same(false),
        );
    }

    #[Test]
    public static function contains(): void
    {
        pipe(
            Str\from('value'),
            Str\contains('val'),
            Type\isSameAs('bool'),
            Assert\same(true),
        );

        pipe(
            Str\from('value'),
            Str\contains('other'),
            Type\isSameAs('bool'),
            Assert\same(false),
        );
    }

    #[Test]
    public static function isEmpty_(): void
    {
        pipe(
            Str\from('value'),
            Str\isEmpty(...),
            Type\isSameAs('bool'),
            Assert\same(false),
        );

        pipe(
            Str\from(''),
            Str\isEmpty(...),
            Type\isSameAs('bool'),
            Assert\same(true),
        );
    }

    #[Test]
    public static function length(): void
    {
        pipe(
            Str\from(''),
            Str\length(...),
            Type\isSameAs('int'),
            Assert\same(0),
        );

        pipe(
            Str\from('abc'),
            Str\length(...),
            Type\isSameAs('int'),
            Assert\same(3),
        );
    }

    #[Test]
    public static function replace(): void
    {
        pipe(
            Str\from('abc'),
            Str\replace('ab', 'bc'),
            Type\isSameAs('string'),
            Assert\same('bcc'),
        );

        pipe(
            Str\from('a b c'),
            Str\replace(' ', ''),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );
    }

    #[Test]
    public static function substring(): void
    {
        pipe(
            Str\from('abc'),
            Str\substring(0, 2),
            Type\isSameAs('string'),
            Assert\same('ab'),
        );
    }

    #[Test]
    public static function split(): void
    {
        pipe(
            Str\from('a, b, c'),
            Str\split(', '),
            Type\isSameAs('non-empty-list<string>'),
            Assert\same(['a', 'b', 'c']),
        );

        pipe(
            Str\from(''),
            Str\split(', '),
            Type\isSameAs('non-empty-list<string>'),
            Assert\same(['']),
        );
    }

    #[Test]
    public static function toUpperCase(): void
    {
        pipe(
            Str\from('abc'),
            Str\toUpperCase(...),
            Type\isSameAs('string'),
            Assert\same('ABC'),
        );

        pipe(
            Str\from('Abc'),
            Str\toUpperCase(...),
            Type\isSameAs('string'),
            Assert\same('ABC'),
        );

        pipe(
            Str\from('ABC'),
            Str\toUpperCase(...),
            Type\isSameAs('string'),
            Assert\same('ABC'),
        );
    }

    #[Test]
    public static function toLowerCase(): void
    {
        pipe(
            Str\from('ABC'),
            Str\toLowerCase(...),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );

        pipe(
            Str\from('aBC'),
            Str\toLowerCase(...),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );

        pipe(
            Str\from('abc'),
            Str\toLowerCase(...),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );
    }

    #[Test]
    public static function trim(): void
    {
        pipe(
            Str\from(' abc'),
            Str\trim(...),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );

        pipe(
            Str\from(' abc '),
            Str\trim(...),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );

        pipe(
            Str\from('  abc  '),
            Str\trim(...),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );
    }

    #[Test]
    public static function trimLeft(): void
    {
        pipe(
            Str\from(' abc'),
            Str\trimLeft(...),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );

        pipe(
            Str\from(' abc '),
            Str\trimLeft(...),
            Type\isSameAs('string'),
            Assert\same('abc '),
        );

        pipe(
            Str\from('  abc  '),
            Str\trimLeft(...),
            Type\isSameAs('string'),
            Assert\same('abc  '),
        );
    }

    #[Test]
    public static function trimRight(): void
    {
        pipe(
            Str\from('abc '),
            Str\trimRight(...),
            Type\isSameAs('string'),
            Assert\same('abc'),
        );

        pipe(
            Str\from(' abc '),
            Str\trimRight(...),
            Type\isSameAs('string'),
            Assert\same(' abc'),
        );

        pipe(
            Str\from('  abc  '),
            Str\trimRight(...),
            Type\isSameAs('string'),
            Assert\same('  abc'),
        );
    }
}
