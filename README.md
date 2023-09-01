# fp4php
Functional programming utilities for PHP.

![psalm level](https://shepherd.dev/github/fp4php/fp4php/level.svg)
![psalm type coverage](https://shepherd.dev/github/fp4php/fp4php/coverage.svg)
![phpunit coverage](https://coveralls.io/repos/github/fp4php/fp4php/badge.svg?branch=master)

This library extensively uses curried functions and the pipe combinator.

If you don't like the following code:
```php
self::someMethod(
    array_filter(
        array_map(
            fn ($i) => $i + 1,
            [1, 2, 3],
        ),
        fn ($i) => $i % 2 === 0,
    );
);
```

Or:
```php
self::someMethod(
    ArrayList::fromIterable([/*...*/]),
        ->map(fn ($i) => $i + 1),
        ->filter(fn ($i) => $i % 2 === 0),
);
```

Then this library might interest you:

```php
<?php

declare(strict_types=1);

use Fp4\PHP\ArrayList as l;

use function Fp4\PHP\Combinator\pipe;

final class App
{
    /**
     * @param list<int> $list
     * @return list<string>
     */
    public function run(array $list): array
    {
        return pipe(
            $list,
            l\map(fn($i) => $i + 1),
            l\filter(fn($i) => 0 === $i % 2),
            self::toString(...),
        );
    }

    /**
     * @param list<int> $list
     * @return list<string>
     */
    public static function toString(array $list): array
    {
        return pipe(
            $list,
            l\map(fn($i) => (string)$i),
        );
    }
}
```

To learn more about this library, read the following:

- [Pipe](./docs/Pipe.md)
- [Option](./docs/Option.md)
- [Either](./docs/Either.md)
- [Psalm](./docs/Psalm.md)
