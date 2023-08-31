## Option overview

`Option<A>` is a container for an optional value of type `A`.
If the value of type `A` is present, the `Option<A>` is an instance of `Some<A>`, containing the present value of type `A`.
If the value is absent, the `Option<A>` is an instance of `None`.

`Option<A>` could be looked at as a collection with either one or zero elements.
Another way to look at `Option<A>` is: it represents the effect of a possibly failing computation.

### Imperative and functional approach comparison

Imperative:

```php
<?php

declare(strict_types=1);

namespace App;

function inverse(int|float $n): ?float
{
    return 0 !== $n ? 1 / $n : null;
}

/**
 * @param list<int> $numbers
 */
function takeFirstInverseNumber(array $numbers): string
{
    $number = $numbers[0] ?? null;

    if (null !== $number) {
        $inverse = inverse($number);

        if (null !== $inverse) {
            return "Inverse number: {$inverse}";
        }
    }

    return 'No result';
}

var_dump(takeFirstInverseNumber([10, 20, 30])); // Inverse number: 0.1
var_dump(takeFirstInverseNumber([0, 20, 30]));  // No result
var_dump(takeFirstInverseNumber([]));           // No result
```

Simple example, but we already have two checks for null and nesting of branches.
Any nesting increases the complexity of perception.

Let's try the functional approach:

```php
<?php

declare(strict_types=1);

namespace App;

use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Option as O;

use function Fp4\PHP\Combinator\pipe;

/**
 * @return O\Option<float>
 */
function inverse(int|float $n): O\Option
{
    return 0 !== $n ? O\some(1 / $n) : O\none;
}

/**
 * @param list<int> $numbers
 */
function takeFirstInverseNumber(array $numbers): string
{
    return pipe(
        L\first($numbers),
        O\flatMap(inverse(...)),
        O\map(fn($inverse) => "Inverse number: {$inverse}"),
        O\getOrCall(fn() => 'No result'),
    );
}

var_dump(takeFirstInverseNumber([10, 20, 30])); // Inverse number: 0.1
var_dump(takeFirstInverseNumber([0, 20, 30]));  // No result
var_dump(takeFirstInverseNumber([]));           // No result
```

Looks like a list of instructions, isn't it?
There is no nesting. Just "what to do if a value exists" and "what to do with a value absence"

### Bindable a.k.a. Do-notation a.k.a. For-comprehension

There are situations when we need to independently get Option values and do something with it.
This may be a problem that leads us back to nesting:

```php
<?php

declare(strict_types=1);

namespace App;

use Fp4\PHP\Option as O;

use function Fp4\PHP\Combinator\pipe;

final class User
{
    public function __construct(
        public readonly int $userId,
        public readonly string $login,
    ) {
    }
}

final class Project
{
    public function __construct(
        public readonly int $projectId,
        public readonly string $name,
    ) {
    }
}

final class ProjectInvite
{
    public function __construct(
        public readonly User $user,
        public readonly Project $project,
    ) {
    }
}

/**
 * @return O\Option<User>
 */
function findUserById(int $userId): O\Option
{
    return 42 === $userId
        ? O\some(new User(42, 'example'))
        : O\none();
}

/**
 * @return O\Option<Project>
 */
function findProjectById(int $projectId): O\Option
{
    return 42 === $projectId
        ? O\some(new Project(42, 'example'))
        : O\none();
}

/**
 * @return O\Option<ProjectInvite>
 */
function makeProjectInviteFlatMap(int $userId, int $projectId): O\Option
{
    return pipe(
        findUserById($userId),
        O\flatMap(fn(User $user) => pipe(
            findProjectById($projectId),
            O\map(fn(Project $project) => new ProjectInvite($user, $project)),
        )),
    );
}
```

We don't have any null checks or other uglies statements. But `makeProjectInviteFlatMap` looks a bit messy.
We got what we wanted, but we would like it to look nice and clear!

Actually, this library has alternative syntax for `flatMap` operation:

```php
<?php

declare(strict_types=1);

namespace App;

use Fp4\PHP\Option as O;

use function Fp4\PHP\Combinator\pipe;

/**
 * @return O\Option<ProjectInvite>
 */
function makeProjectInviteBindable(int $userId, int $projectId): O\Option
{
    return pipe(
        O\bindable(),
        O\bind(
            user: fn() => findUserById($userId),
            project: fn() => findProjectById($projectId),
        ),
        O\map(fn($i) => new ProjectInvite($i->user, $i->project)),
    );
}
```

This snippet does semantically same things as `flatMap` example does, but looks cleaner.

### Bindable scope

`bind` operation can associate left-hand side expression with a given name at right-hand side.
Bound value will be available in the future.
Look at example:

```php
<?php

declare(strict_types=1);

namespace App;

use Fp4\PHP\Option as O;

use function Fp4\PHP\Combinator\pipe;

/**
 * @return O\Option<int>
 */
function abc(): O\Option
{
    return pipe(
        O\bindable(),
        O\bind(
            a: fn() => O\some(1),
            b: fn() => O\some(41),
            // consume previously declared values
            c: fn($i) => O\some($i->a + $i->b),
        ),
        O\map(fn($i) => $i->c),
    );
}

var_dump(abc()); // 42
```

We bound `a` and `b` and sum it to `c`.
This can be felt as variable declaration.

`bind` operation requires `Fp4\PHP\Bindable` instance as an input.
So by this reason before `bind` we call `bindable` operation.

`bind` can be called as many times as needed:

```php
<?php

declare(strict_types=1);

namespace App;

use Fp4\PHP\Option as O;

use function Fp4\PHP\Combinator\pipe;

/**
 * @return O\Option<int>
 */
function abc(): O\Option
{
    return pipe(
        O\bindable(),
        O\bind(a: fn() => O\some(1)),
        O\bind(b: fn() => O\some(41)),
        O\bind(c: fn($i) => O\some($i->a + $i->b)),
        O\map(fn($i) => $i->c),
    );
}

var_dump(abc()); // 42
```

If you need to declare just a pure value inside `Bindable` context, you can use `let` operation:

```php
<?php

declare(strict_types=1);

namespace App;

use Fp4\PHP\Option as O;

use function Fp4\PHP\Combinator\pipe;

/**
 * @return O\Option<int>
 */
function abc(): O\Option
{
    return pipe(
        O\bindable(),
        O\bind(
            a: fn() => O\some(1),
            b: fn() => O\some(41),
        ),
        O\let(c: fn($i) => $i->a + $i->b),
        O\map(fn($i) => $i->c),
    );
}

var_dump(abc()); // 42
```

### Interoperability

Your old code may return values that are not suitable for convenient work with this library.
This does not mean that you need to go and rewrite everything.
You can use interop operations:

```php
<?php

declare(strict_types=1);

namespace App;

use Fp4\PHP\Option as O;

use function Fp4\PHP\Combinator\pipe;

final class WithNullableProperty
{
    public function __construct(
        public readonly int|null $value,
    ) {
    }
}

/**
 * @return O\Option<int>
 */
function returnsOption(): O\Option
{
    return O\some(1);
}

function returnsNullable(): ?int
{
    return rand(0, 1) ? 1 : null;
}

function mayThrows(): int
{
    return rand(0, 1) ? 40 : throw new \RuntimeException();
}

/**
 * @return O\Option<WithNullableProperty>
 */
function findSomething(): O\Option
{
    return O\some(new WithNullableProperty());
}

/**
 * @return O\Option<int>
 */
function fromNullable(): O\Option
{
    return pipe(
        O\bindable(),
        O\bind(
            // Returns Option<int>. Can be bind as is.
            a: returnsOption(),
            // Returns int|null. It needs to be lifted to Option<int>.
            b: O\fromNullable(returnsNullable()),
            // If exception happens, it returns None, otherwise, Some.
            c: O\tryCatch(fn() => mayThrows()),
            // flatMapNullable expects T|null instead of Option<T>
            d: pipe(
                findSomething(),
                O\flatMapNullable(fn(WithNullableProperty $obj) => $obj->value),
            ),
        ),
        O\map(fn($i) => $i->a + $i->b + $i->c),
    );
}

var_dump(abc()); // 42
```
