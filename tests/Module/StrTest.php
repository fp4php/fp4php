<?php

declare(strict_types=1);

namespace Fp4\PHP\Test\Module;

use Fp4\PHP\Module\PHPUnit as Assert;
use Fp4\PHP\Module\Str;
use Fp4\PHP\Module\Psalm as Type;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function Fp4\PHP\Module\Functions\pipe;

final class StrTest extends TestCase
{
    #[Test]
    public static function prepend(): void
    {
        pipe(
            'val',
            Str\prepend('pref-'),
            Type\isSameAs('non-empty-string'),
            Assert\same('pref-val'),
        );
    }
}
