# Pipe

The basic building block of fp4php/fp4php is the Pipe operator.
Intuitively, you can use the operator to chain a sequence of functions from left-to-right.
The pipe takes an arbitrary number of arguments.
The first argument can be any arbitrary value, and subsequent arguments must be functions of arity one.
The return-type of a preceding function in the pipeline must match the input type of the subsequent function.

Look at example:

```php
<?php

declare(strict_types=1);

use function Fp4\PHP\Combinator\pipe;

function add1(int $num): int
{
    return $num + 1;
}

function multiply2(int $num): int
{
    return $num * 2;
}

$result = pipe(1, add1(...), multiply2(...));
var_dump($result); // 4
```

The result of this operation is `4`. How did we arrive at this result? Let’s look at the steps:
- We start with the value of `1`.
- `1` is piped into the first argument of `add1`, and `add1` is evaluated to `2` by adding `1`.
- The return value of `add1`, `2` is piped into the first argument `multiply2` and is evaluated to `4` by multiplying by `2`.

Currently, our pipeline inputs a number and outputs a new number.
Is it possible to transform the input type to another type, like a string?
The answer is yes. Let’s add a toString function at the end of the pipeline.

```php
<?php

declare(strict_types=1);

use function Fp4\PHP\Combinator\pipe;

function toString(int $num): string
{
    return (string) $num;
}

function add1(int $num): int
{
    return $num + 1;
}

function multiply2(int $num): int
{
    return $num * 2;
}

$result = pipe(1, add1(...), multiply2(...), toString(...));
var_dump($result); // '4'
```

Now our pipeline evaluates to `'4'`.
What happens if we were to put `toString` between `add1` and `multiply2`?
We get a Psalm error because the output type of `toString`, `string` does not match the input type of `multiply2`, `int`.

```php
<?php

declare(strict_types=1);

use function Fp4\PHP\Combinator\pipe;

function toString(int $num): string
{
    return (string) $num;
}

function add1(int $num): int
{
    return $num + 1;
}

function multiply2(int $num): int
{
    return $num * 2;
}

$result = pipe(1, add1(...), toString(...), multiply2(...));
var_dump($result); // '4'
```

```
ERROR: PipeTypeMismatch
at /home/user/fp4php/some-file.php:22:45
Type string should be a subtype of int for function impure-Closure(int):int.
$result = pipe(1, add1(...), toString(...), multiply2(...));
                                            ^^^^^^^^^^^^^^
```

In short, you can use the pipe operator to transform any value using a sequence of functions.
