<?php

declare(strict_types=1);

namespace Fp4\PHP\Module\ArrayList;

use Closure;
use Fp4\PHP\Type\Bindable;

/**
 * @return list<Bindable>
 */
function bindable(): array
{
    return [new Bindable()];
}

/**
 * @param Closure(Bindable): list<mixed> ...$params
 * @return Closure(list<Bindable>): list<Bindable>
 */
function bind(Closure ...$params): Closure
{
    return function(array $list) use ($params) {
        foreach ($params as $key => $param) {
            $cartesian = [];

            foreach ($list as $bindable) {
                foreach ($param($bindable) as $item) {
                    $cartesian[] = $bindable->with((string) $key, $item);
                }
            }

            $list = $cartesian;
        }

        return $list;
    };
}

/**
 * @param Closure(Bindable): mixed ...$params
 * @return Closure(list<Bindable>): list<Bindable>
 */
function let(Closure ...$params): Closure
{
    return function(array $list) use ($params) {
        foreach ($params as $key => $param) {
            $cartesian = [];

            foreach ($list as $bindable) {
                $cartesian[] = $bindable->with((string) $key, $param($bindable));
            }

            $list = $cartesian;
        }

        return $list;
    };
}
