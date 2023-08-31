<?php

declare(strict_types=1);

namespace Fp4\PHP\Evidence;

use Closure;
use Fp4\PHP\Option as O;
use Fp4\PHP\Option\Option;

use function Fp4\PHP\Combinator\pipe;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;

/**
 * @return Option<int>
 */
function proveInt(mixed $value): Option
{
    return is_int($value) ? O\some($value) : O\none;
}

/**
 * @return Option<float>
 */
function proveFloat(mixed $value): Option
{
    return is_float($value) ? O\some($value) : O\none;
}

/**
 * @return Option<string>
 */
function proveString(mixed $value): Option
{
    return is_string($value) ? O\some($value) : O\none;
}

/**
 * @return Option<non-empty-string>
 */
function proveNonEmptyString(mixed $value): Option
{
    return pipe(
        $value,
        proveString(...),
        O\flatMap(fn(string $v) => '' !== $v ? O\some($v) : O\none),
    );
}

/**
 * @return Option<bool>
 */
function proveBool(mixed $value): Option
{
    return is_bool($value) ? O\some($value) : O\none;
}

/**
 * @return Option<true>
 */
function proveTrue(mixed $value): Option
{
    /** @var Option<true> */
    return O\fromNullable(true === $value ? $value : null);
}

/**
 * @return Option<false>
 */
function proveFalse(mixed $value): Option
{
    /** @var Option<false> */
    return O\fromNullable(false === $value ? $value : null);
}

/**
 * @return Option<null>
 */
function proveNull(mixed $value): Option
{
    return null === $value ? O\some($value) : O\none;
}

/**
 * @return Option<object>
 */
function proveObject(mixed $value): Option
{
    return is_object($value) ? O\some($value) : O\none;
}

/**
 * @template A
 *
 * @param class-string<A>|non-empty-list<class-string<A>> $fqcn
 * @return Closure(mixed): Option<A>
 */
function proveOf(string|array $fqcn, bool $invariant = false): Closure
{
    return function(mixed $value) use ($fqcn, $invariant) {
        if (!is_object($value)) {
            return O\none;
        }

        foreach (is_array($fqcn) ? $fqcn : [$fqcn] as $class) {
            if ($invariant ? $value::class === $class : is_a($value, $class)) {
                /** @var A $value */
                return O\some($value);
            }
        }

        return O\none;
    };
}

/**
 * @template A
 *
 * @param non-empty-list<callable(mixed): Option<A>> $evidences
 * @return Closure(mixed): Option<A>
 */
function proveUnion(array $evidences): Closure
{
    return function(mixed $value) use ($evidences) {
        foreach ($evidences as $evidence) {
            $option = $evidence($value);

            if (O\isSome($option)) {
                return $option;
            }
        }

        return O\none;
    };
}

/**
 * @template V
 * @param iterable<V> $iterable
 * @return Option<list<V>>
 */
function proveList(iterable $iterable): Option
{
    return is_array($iterable) && array_is_list($iterable)
        ? O\some($iterable)
        : O\none;
}

/**
 * @template A
 *
 * @param callable(mixed): Option<A> $valueEvidence
 * @return Closure(mixed): Option<list<A>>
 */
function proveListOf(callable $valueEvidence): Closure
{
    return function(mixed $value) use ($valueEvidence) {
        if (!is_array($value) || !array_is_list($value)) {
            return O\none;
        }

        $list = [];

        foreach ($value as $i) {
            $item = $valueEvidence($i);

            if (O\isSome($item)) {
                $list[] = $item->value;
            } else {
                return O\none;
            }
        }

        return O\some($list);
    };
}

/**
 * @template V
 * @param iterable<V> $iterable
 * @return Option<non-empty-list<V>>
 */
function proveNonEmptyList(iterable $iterable): Option
{
    return pipe(
        proveList($iterable),
        O\flatMap(fn(array $list) => [] !== $list ? O\some($list) : O\none),
    );
}

/**
 * @template A
 *
 * @param callable(mixed): Option<A> $valueEvidence
 * @return Closure(mixed): Option<non-empty-list<A>>
 */
function proveNonEmptyListOf(callable $valueEvidence): Closure
{
    return fn(mixed $value) => pipe(
        $value,
        proveListOf($valueEvidence),
        O\flatMap(fn($list) => [] !== $list ? O\some($list) : O\none),
    );
}

/**
 * @template K
 * @template V
 * @param iterable<K, V> $iterable
 * @return Option<array<K, V>>
 */
function proveArray(iterable $iterable): Option
{
    return is_array($iterable)
        ? O\some($iterable)
        : O\none;
}

/**
 * @template K of array-key
 * @template A
 *
 * @param callable(mixed): Option<K> $proveKey
 * @param callable(mixed): Option<A> $proveValue
 * @return Closure(mixed): Option<array<K, A>>
 */
function proveArrayOf(callable $proveKey, callable $proveValue): Closure
{
    return function(mixed $value) use ($proveKey, $proveValue) {
        if (!is_array($value)) {
            return O\none;
        }

        $array = [];

        foreach ($value as $k => $i) {
            $key = $proveKey($k);
            $item = $proveValue($i);

            if (O\isSome($key) && O\isSome($item)) {
                $array[$key->value] = $item->value;
            } else {
                return O\none;
            }
        }

        return O\some($array);
    };
}

/**
 * @template K
 * @template V
 * @param iterable<K, V> $iterable
 * @return Option<non-empty-array<K, V>>
 */
function proveNonEmptyArray(iterable $iterable): Option
{
    return pipe(
        proveArray($iterable),
        O\flatMap(fn($array) => [] !== $array ? O\some($array) : O\none),
    );
}

/**
 * @template K of array-key
 * @template A
 *
 * @param callable(mixed): Option<K> $proveKey
 * @param callable(mixed): Option<A> $proveValue
 * @return Closure(mixed): Option<non-empty-array<K, A>>
 */
function proveNonEmptyArrayOf(callable $proveKey, callable $proveValue): Closure
{
    return fn(mixed $value) => pipe(
        $value,
        proveArrayOf($proveKey, $proveValue),
        O\flatMap(fn($list) => [] !== $list ? O\some($list) : O\none),
    );
}
