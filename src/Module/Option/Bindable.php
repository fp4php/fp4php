<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\Option;

use Closure;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;

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
            return none();
        }

        $bindable = $context->value;

        foreach ($params as $key => $param) {
            $result = $param($bindable);

            if (isNone($result)) {
                return none();
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
            return none();
        }

        $bindable = $context->value;

        foreach ($params as $key => $param) {
            $bindable = $bindable->with((string) $key, $param($bindable));
        }

        return some($bindable);
    };
}
