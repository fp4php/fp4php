## Either overview

Represents a value of one of two possible types (a disjoint union).

An instance of `Either` is either an instance of `Left` or `Right`.

A common use of `Either` is as an alternative to `Option` for dealing with possible missing values.
In this usage, `None` is replaced with a `Left` which can contain useful information.
`Right` takes the place of `Some`.

### Imperative and functional approach comparison

This example should be familiar to you by previous example for Option.
But instead `null` we use exceptions.

```php
<?php

declare(strict_types=1);

function inverse(int $number): float
{
    if (0 === $number) {
        throw new \Error('Cannot divide by zero');
    }

    return 1 / $number;
}

/**
 * @param list<int> $numbers
 */
function head(array $numbers): int
{
    if ([] === $numbers) {
        throw new \Error('Empty array');
    }

    return $numbers[0];
}

/**
 * @param list<int> $numbers
 */
function takeFirstInverseNumber(array $numbers): string
{
    try {
        $result = inverse(head($numbers));

        return "Result is: {$result}";
    } catch (Error $e) {
        return "Error is: {$e}";
    }
}

var_dump(takeFirstInverseNumber([10, 20, 30])); // Result is: 0.1
var_dump(takeFirstInverseNumber([0, 20, 30]));  // Error is: Cannot divide by zero
var_dump(takeFirstInverseNumber([]));           // Error is: Empty array
```

The example above has problems:
1) There are no `throws` docblocks. We should look at function implementations to understand what should and shouldn't be caught.
2) We can forget to catch an exception. Yes, static analysis can force it, but we don't have `throws` docblocks.
3) Static analysis for catching exceptions doesn't work well and has the big hole: https://psalm.dev/r/bfb26e0ddf
4) Ugly nesting.

Functional approach:

```php
<?php

declare(strict_types=1);

use Fp4\PHP\Either as E;

use function Fp4\PHP\Combinator\pipe;

final class AppError
{
    public function __construct(
        public readonly string $message,
    ) {
    }
}

/**
 * @return E\Either<AppError, float>
 */
function inverse(int $number): E\Either
{
    return 0 === $number
        ? E\left(new AppError('Cannot divide by zero'))
        : E\right(1 / $number);
}

/**
 * @param list<int> $numbers
 * @return E\Either<AppError, int>
 */
function head(array $numbers): E\Either
{
    return [] === $numbers
        ? E\left(new AppError('Empty array'))
        : E\right($numbers[0]);
}

/**
 * @param list<int> $numbers
 */
function takeFirstInverseNumber(array $numbers): string
{
    return pipe(
        head($numbers),
        E\flatMap(inverse(...)),
        E\map(fn($result) => "Result is: {$result}"),
        E\mapLeft(fn($error) => "Error is: {$error->message}"),
        E\unwrap(...),
    );
}

var_dump(takeFirstInverseNumber([10, 20, 30])); // Result is: 0.1
var_dump(takeFirstInverseNumber([0, 20, 30]));  // Error is: Cannot divide by zero
var_dump(takeFirstInverseNumber([]));           // Error is: Empty array
```

Convention dictates that `Left` is used for failure and `Right` is used for success.

1) We see all possible outcomes at type-level.
2) When working with `Either`, it becomes more difficult to forget about error handling. If it is necessary, then it will need to be done explicitly!
3) No nesting.

### Bindable a.k.a. Do-notation a.k.a. For-comprehension

`Either` same as `Option` has `bind` and `bindable` operations:

```php
<?php

declare(strict_types=1);

use Fp4\PHP\Either as E;

use function Fp4\PHP\Combinator\pipe;

final class User
{
    public function __construct(
        public readonly int $userId,
        public readonly string $login,
    ) {
    }
}
final class UserNotFound {}

final class Project
{
    public function __construct(
        public readonly int $projectId,
        public readonly string $name,
    ) {
    }
}
final class ProjectNotFound {}

final class ProjectInvite
{
    public function __construct(
        public readonly User $user,
        public readonly Project $project,
    ) {
    }
}

/**
 * @return E\Either<UserNotFound, User>
 */
function findUserById(int $userId): E\Either
{
    return 42 === $userId
        ? E\right(new User(42, 'example'))
        : E\left(new UserNotFound());
}

/**
 * @return E\Either<ProjectNotFound, Project>
 */
function findProjectById(int $projectId): E\Either
{
    return 42 === $projectId
        ? E\right(new Project(42, 'example'))
        : E\left(new ProjectNotFound());
}

/**
 * @return E\Either<UserNotFound|ProjectNotFound, ProjectInvite>
 */
function makeProjectInviteFlatMap(int $userId, int $projectId): E\Either
{
    return pipe(
        findUserById($userId),
        E\flatMap(fn(User $user) => pipe(
            findProjectById($projectId),
            E\map(fn(Project $project) => new ProjectInvite($user, $project)),
        )),
    );
}

/**
 * @return E\Either<UserNotFound|ProjectNotFound, ProjectInvite>
 */
function makeProjectInviteBindable(int $userId, int $projectId): E\Either
{
    return pipe(
        E\bindable(),
        E\bind(
            user: fn() => findUserById($userId),
            project: fn() => findProjectById($projectId),
        ),
        E\map(fn($i) => new ProjectInvite($i->user, $i->project)),
    );
}
```

See to type `E\Either<UserNotFound|ProjectNotFound, ProjectInvite>`.
Psalm can infer all possible errors that can happen.

### Interoperability

With your old exceptional api, you can interact via `tryCatch` operation:

```php
<?php

declare(strict_types=1);

use Fp4\PHP\Either as E;

use function Fp4\PHP\Combinator\pipe;

final class YourErrorStructure
{
    public function __construct(
        public readonly int $code,
        public readonly string $message,
    ) {}
}

function mayThrows(): int
{
    throw new \RuntimeException('Error');
}

/**
 * @return E\Either<YourErrorStructure, int>
 */
function interop(): E\Either
{
    return pipe(
        E\tryCatch(fn() => mayThrows()),
        E\map(fn(int $a) => $a + 1)
        E\mapLeft(fn(Throwable $e) => new YourErrorStructure(
            code: $e->getCode(),
            message: $e->getMessage(),
        )),
    );
}
```
