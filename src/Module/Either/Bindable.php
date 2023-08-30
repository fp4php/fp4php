<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Either;

use Closure;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Either;

/**
 * @return Either<never, Bindable>
 */
function bindable(): Either
{
    return right(new Bindable());
}

/**
 * @param Closure(Bindable): Either ...$params
 * @return Closure(Either<mixed, Bindable>): Either<mixed, Bindable>
 */
function bind(Closure ...$params): Closure
{
    return function(Either $context) use ($params) {
        if (isLeft($context)) {
            return $context;
        }

        $bindable = $context->value;

        foreach ($params as $key => $param) {
            $result = $param($bindable);

            if (isLeft($result)) {
                return $result;
            }

            $bindable = $bindable->with((string) $key, $result->value);
        }

        return right($bindable);
    };
}

/**
 * @param Closure(Bindable): mixed ...$params
 * @return Closure(Either<mixed, Bindable>): Either<mixed, Bindable>
 */
function let(Closure ...$params): Closure
{
    return function(Either $context) use ($params) {
        if (isLeft($context)) {
            return $context;
        }

        $bindable = $context->value;

        foreach ($params as $key => $param) {
            $bindable = $bindable->with((string) $key, $param($bindable));
        }

        return right($bindable);
    };
}
