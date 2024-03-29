<?php

declare(strict_types=1);

namespace Fp4\PHP\Test;

use Fp4\PHP\PHPUnit as Assert;
use Fp4\PHP\PsalmIntegration as Type;
use Fp4\PHP\Shape as S;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Combinator\pipe;

/**
 * @api
 */
final class ShapeTest extends TestCase
{
    #[Test]
    public static function from(): void
    {
        pipe(
            S\from(['fst' => 1, 'snd' => 2, 'thr' => 'str']),
            Type\isSameAs('array{fst: int, snd: int, thr: string}'),
            Assert\equals(['fst' => 1, 'snd' => 2, 'thr' => 'str']),
        );
    }

    #[Test]
    public static function fromLiteral(): void
    {
        pipe(
            S\fromLiteral(['fst' => 1, 'snd' => 2, 'thr' => 'str']),
            Type\isSameAs('array{fst: 1, snd: 2, thr: "str"}'),
            Assert\equals(['fst' => 1, 'snd' => 2, 'thr' => 'str']),
        );
    }
}
