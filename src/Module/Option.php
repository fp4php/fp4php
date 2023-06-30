<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Option;

use Closure;
use Fp4\PHP\Module\Evidence as E;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\None;
use Fp4\PHP\Type\Option;
use Fp4\PHP\Type\Some;
use Throwable;

use function Fp4\PHP\Module\Functions\pipe;

// region: constructor

/**
 * @template A
 *
 * @param A $value
 * @return Option<A>
 */
function some(mixed $value): Option
{
    return new Some($value);
}

const none = new None();

/**
 * @template A
 *
 * @param A $value
 * @return Option<A>
 */
function fromLiteral(mixed $value): Option
{
    return some($value);
}

/**
 * @template A
 *
 * @param A|null $value
 * @return ($value is null ? None : Option<A>)
 */
function fromNullable(mixed $value): Option
{
    return null !== $value ? some($value) : none;
}

/**
 * @template A
 *
 * @param A|null $value
 * @return ($value is null ? None : Option<A>)
 */
function fromNullableLiteral(mixed $value): Option
{
    return fromNullable($value);
}

/**
 * @template A
 *
 * @param callable(): A $callback
 * @return Option<A>
 */
function tryCatch(callable $callback): Option
{
    try {
        return some($callback());
    } catch (Throwable) {
        return none;
    }
}

/**
 * @template A
 *
 * @param Closure(): Option<A> $head
 * @param Closure(): Option<A> ...$tail
 * @return Option<A>
 */
function first(Closure $head, Closure ...$tail): Option
{
    foreach ([$head, ...$tail] as $option) {
        $o = $option();

        if (isSome($o)) {
            return $o;
        }
    }

    return none;
}

/**
 * @template A
 *
 * @param callable(): A $some
 * @return Option<A>
 */
function when(bool $cond, callable $some): Option
{
    return $cond
        ? some($some())
        : none;
}

// endregion: constructor

// region: destructors

/**
 * @template A
 *
 * @param Option<A> $option
 * @return A|null
 */
function getOrNull(Option $option): mixed
{
    return isSome($option)
        ? $option->value
        : null;
}

/**
 * @template A
 * @template B
 *
 * @param B $value
 * @return Closure(Option<A>): (A|B)
 */
function getOrElse(mixed $value): Closure
{
    return fn(Option $option) => isSome($option)
        ? $option->value
        : $value;
}

/**
 * @template A
 * @template B
 *
 * @param callable(): B $call
 * @return Closure(Option<A>): (A|B)
 */
function getOrCall(callable $call): Closure
{
    return fn(Option $option) => isSome($option)
        ? $option->value
        : $call();
}

/**
 * @template A
 * @template TNone
 * @template TSome
 *
 * @param callable(): TNone $ifNone
 * @param callable(A): TSome $ifSome
 * @return Closure(Option<A>): (TNone|TSome)
 */
function fold(callable $ifNone, callable $ifSome): Closure
{
    return fn(Option $option) => isSome($option)
        ? $ifSome($option->value)
        : $ifNone();
}

// endregion: destructors

// region: refinements

/**
 * @template A
 *
 * @param Option<A> $option
 * @psalm-assert-if-true Some<A> $option
 */
function isSome(Option $option): bool
{
    return $option instanceof Some;
}

/**
 * @template A
 *
 * @param Option<A> $option
 * @psalm-assert-if-true None $option
 */
function isNone(Option $option): bool
{
    return $option instanceof None;
}

// endregion: refinements

// region: ops

/**
 * @template A
 * @template B
 *
 * @param callable(A): B $callback
 * @return Closure(Option<A>): Option<B>
 */
function map(callable $callback): Closure
{
    return fn(Option $option) => isSome($option)
        ? some($callback($option->value))
        : none;
}

/**
 * @template A
 *
 * @param callable(A): void $callback
 * @return Closure(Option<A>): Option<A>
 */
function tap(callable $callback): Closure
{
    return function(Option $option) use ($callback) {
        if (isSome($option)) {
            $callback($option->value);

            return some($option->value);
        }

        return none;
    };
}

/**
 * @template A
 * @template B
 *
 * @param callable(A): Option<B> $callback
 * @return Closure(Option<A>): Option<B>
 */
function flatMap(callable $callback): Closure
{
    return fn(Option $option) => isSome($option)
        ? $callback($option->value)
        : none;
}

/**
 * @template A
 *
 * @param Option<Option<A>> $option
 * @return Option<A>
 */
function flatten(Option $option): Option
{
    return isSome($option) ? $option->value : none;
}

/**
 * @template A
 * @template B
 *
 * @param A $value
 * @return Closure(Option<Closure(A): B>): Option<B>
 */
function ap(mixed $value): Closure
{
    return fn(Option $option) => isSome($option)
        ? some(($option->value)($value))
        : none;
}

/**
 * @template A
 * @template B
 *
 * @param callable(): Option<B> $callback
 * @return Closure(Option<A>): Option<A|B>
 */
function orElse(callable $callback): Closure
{
    return fn(Option $option) => isSome($option)
        ? some($option->value)
        : $callback();
}

/**
 * @template A
 *
 * @param callable(A): bool $callback
 * @return Closure(Option<A>): Option<A>
 */
function filter(callable $callback): Closure
{
    return fn(Option $a) => isSome($a) && $callback($a->value)
        ? some($a->value)
        : none;
}

/**
 * @template A
 * @template B
 *
 * @param class-string<B>|non-empty-list<class-string<B>> $fqcn
 * @return Closure(Option<A>): Option<B>
 */
function filterOf(string|array $fqcn, bool $invariant = false): Closure
{
    return fn(Option $a) => pipe(
        $a,
        flatMap(E\proveOf($fqcn, $invariant)),
    );
}

// endregion: ops

// region: bindable

/**
 * @return Option<Bindable>
 */
function bindable(): Option
{
    return some(new Bindable());
}

/**
 * @param Closure(Bindable): Option ...$params
 * @return Closure(Option<Bindable>): Option<Bindable>
 */
function bind(Closure ...$params): Closure
{
    return function(Option $context) use ($params) {
        if (isNone($context)) {
            return none;
        }

        $bindable = $context->value;

        foreach ($params as $key => $param) {
            $result = $param($bindable);

            if (isNone($result)) {
                return none;
            }

            $bindable = $bindable->with((string) $key, $result->value);
        }

        return some($bindable);
    };
}

/**
 * @param Closure(Bindable): mixed ...$params
 * @return Closure(Option<Bindable>): Option<Bindable>
 */
function let(Closure ...$params): Closure
{
    return function(Option $context) use ($params) {
        if (isNone($context)) {
            return none;
        }

        $bindable = $context->value;

        foreach ($params as $key => $param) {
            $bindable = $bindable->with((string) $key, $param($bindable));
        }

        return some($bindable);
    };
}

// endregion: bindable
