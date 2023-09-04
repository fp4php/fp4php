<?php

declare(strict_types=1);

namespace Fp4\PHP\Test;

use Fp4\PHP\Pair as P;
use Fp4\PHP\PHPUnit as Assert;
use Fp4\PHP\PsalmIntegration as Type;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Combinator\pipe;

/**
 * @api
 */
final class PariTest extends TestCase
{
    #[Test]
    public static function from(): void
    {
        pipe(
            P\from('left-value', 'right-value'),
            Type\isSameAs('list{string, string}'),
            Assert\equals(['left-value', 'right-value']),
        );
    }

    #[Test]
    public static function map(): void
    {
        pipe(
            P\from('left-value', 'right-value'),
            P\map(fn($i) => ['boxed' => $i]),
            Type\isSameAs('list{string, array{boxed: string}}'),
            Assert\equals(['left-value', ['boxed' => 'right-value']]),
        );
    }

    #[Test]
    public static function mapLeft(): void
    {
        pipe(
            P\from('left-value', 'right-value'),
            P\mapLeft(fn($i) => ['boxed' => $i]),
            Type\isSameAs('list{array{boxed: string}, string}'),
            Assert\equals([['boxed' => 'left-value'], 'right-value']),
        );
    }

    #[Test]
    public static function left(): void
    {
        pipe(
            P\from('test', 42),
            P\left(...),
            Type\isSameAs('string'),
            Assert\equals('test'),
        );
    }

    #[Test]
    public static function right(): void
    {
        pipe(
            P\from('test', 42),
            P\right(...),
            Type\isSameAs('int'),
            Assert\equals(42),
        );
    }
}
