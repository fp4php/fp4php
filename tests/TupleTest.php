<?php

declare(strict_types=1);

namespace Fp4\PHP\Test;

use Fp4\PHP\PHPUnit as Assert;
use Fp4\PHP\PsalmIntegration as Type;
use Fp4\PHP\Tuple as T;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Combinator\pipe;

/**
 * @api
 */
final class TupleTest extends TestCase
{
    #[Test]
    public static function from(): void
    {
        pipe(
            T\from([1, 2, 'str']),
            Type\isSameAs('array{int, int, string}'),
            Assert\equals([1, 2, 'str']),
        );
    }

    #[Test]
    public static function fromLiteral(): void
    {
        pipe(
            T\fromLiteral([1, 2, 'str']),
            Type\isSameAs('array{1, 2, "str"}'),
            Assert\equals([1, 2, 'str']),
        );
    }
}
