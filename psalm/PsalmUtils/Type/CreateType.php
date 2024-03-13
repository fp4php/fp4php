<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Closure;
use Fp4\PHP\ArrayDictionary as D;
use Fp4\PHP\ArrayList as L;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableObject;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\pipe;
use function is_array;

/**
 * @api
 * @psalm-type IntersectionType = TNamedObject|TTemplateParam|TIterable|TObjectWithProperties|TCallableObject
 */
final class CreateType
{
    /**
     * @param Union|Atomic|non-empty-list<Atomic> $type
     */
    public function union(array|Atomic|Union $type): Union
    {
        if ($type instanceof Union) {
            return $type;
        }

        return new Union($type instanceof Atomic ? [$type] : $type);
    }

    /**
     * @return Closure(Atomic|Union): FunctionLikeParameter
     */
    public function param(string $name, bool $byRef = false, bool $isOptional = false): Closure
    {
        return fn(Atomic|Union $type) => new FunctionLikeParameter(
            name: $name,
            by_ref: $byRef,
            type: self::union($type),
            is_optional: $isOptional,
        );
    }

    /**
     * @param list<FunctionLikeParameter>|FunctionLikeParameter $params
     */
    public function closureAtomic(array|FunctionLikeParameter $params = [], Atomic|Union $return = new TMixed()): TClosure
    {
        return new TClosure(
            value: 'Closure',
            params: is_array($params) ? $params : [$params],
            return_type: self::union($return),
        );
    }

    /**
     * @param list<FunctionLikeParameter>|FunctionLikeParameter $params
     */
    public function closure(array|FunctionLikeParameter $params = [], Atomic|Union $return = new TMixed()): Union
    {
        return pipe(
            self::closureAtomic($params, $return),
            self::union(...),
        );
    }

    /**
     * @param list<FunctionLikeParameter>|FunctionLikeParameter $params
     */
    public function callableAtomic(array|FunctionLikeParameter $params = [], Atomic|Union $return = new TMixed()): TCallable
    {
        return new TCallable(
            params: is_array($params) ? $params : [$params],
            return_type: self::union($return),
        );
    }

    /**
     * @param list<FunctionLikeParameter>|FunctionLikeParameter $params
     */
    public function callable(array|FunctionLikeParameter $params = [], Atomic|Union $return = new TMixed()): Union
    {
        return pipe(
            self::closureAtomic($params, $return),
            self::union(...),
        );
    }

    public function neverAtomic(): TNever
    {
        return new TNever();
    }

    public function never(): Union
    {
        return pipe(
            self::neverAtomic(),
            self::union(...),
        );
    }

    public function mixedAtomic(): TMixed
    {
        return new TMixed();
    }

    public function mixed(): Union
    {
        return pipe(
            self::mixedAtomic(),
            self::union(...),
        );
    }

    public function listAtomic(Atomic|Union $type): TKeyedArray
    {
        return pipe(
            self::union($type),
            Type::getListAtomic(...),
        );
    }

    public function list(Atomic|Union $type): Union
    {
        return pipe(
            self::listAtomic($type),
            self::union(...),
        );
    }

    public function nonEmptyListAtomic(Atomic|Union $type): TKeyedArray
    {
        return pipe(
            self::union($type),
            Type::getNonEmptyListAtomic(...),
        );
    }

    public function nonEmptyList(Atomic|Union $type): Union
    {
        return pipe(
            self::nonEmptyListAtomic($type),
            self::union(...),
        );
    }

    public function arrayAtomic(Atomic|Union $key = new TMixed(), Atomic|Union $value = new TMixed()): TArray
    {
        return new TArray([
            self::union($key),
            self::union($value),
        ]);
    }

    public function array(Atomic|Union $key = new TMixed(), Atomic|Union $value = new TMixed()): Union
    {
        return pipe(
            self::arrayAtomic($key, $value),
            self::union(...),
        );
    }

    public function nonEmptyArrayAtomic(Atomic|Union $key = new TMixed(), Atomic|Union $value = new TMixed()): TArray
    {
        return new TNonEmptyArray([
            self::union($key),
            self::union($value),
        ]);
    }

    public function nonEmptyArray(Atomic|Union $key = new TMixed(), Atomic|Union $value = new TMixed()): Union
    {
        return pipe(
            self::nonEmptyArrayAtomic($key, $value),
            self::union(...),
        );
    }

    /**
     * @param array<string|int, Union|Atomic> $properties
     */
    public function objectWithPropertiesAtomic(array $properties): TObjectWithProperties
    {
        return new TObjectWithProperties(pipe(
            $properties,
            D\map(fn(Atomic|Union $t) => $t instanceof Atomic ? self::union($t) : $t),
        ));
    }

    /**
     * @param array<string|int, Union|Atomic> $properties
     */
    public function objectWithProperties(array $properties): Union
    {
        return pipe(
            self::objectWithPropertiesAtomic($properties),
            self::union(...),
        );
    }

    /**
     * @param IntersectionType|list<IntersectionType> $withIntersections
     * @return Closure(Union|Atomic|non-empty-list<Union|Atomic>): TGenericObject
     */
    public function genericObjectAtomic(string $class, array|Atomic $withIntersections = []): Closure
    {
        return fn(array|Atomic|Union $type_params) => new TGenericObject(
            value: $class,
            type_params: match (true) {
                $type_params instanceof Union => [$type_params],
                $type_params instanceof Atomic => [new Union([$type_params])],
                default => pipe(
                    $type_params,
                    L\map(fn(Atomic|Union $t) => $t instanceof Atomic ? new Union([$t]) : $t),
                ),
            },
            extra_types: pipe(
                is_array($withIntersections) ? $withIntersections : [$withIntersections],
                L\reindex(fn(Atomic $a) => $a->getKey()),
            ),
        );
    }

    /**
     * @param IntersectionType|list<IntersectionType> $withIntersections
     * @return Closure(Union|Atomic|non-empty-list<Union|Atomic>): Union
     */
    public function genericObject(string $class, array|Atomic $withIntersections = []): Closure
    {
        return fn(array|Atomic|Union $type_params) => pipe(
            $type_params,
            self::genericObjectAtomic($class, $withIntersections),
            self::union(...),
        );
    }

    public function namedObject(string $class): Union
    {
        return pipe(
            $class,
            self::namedObjectAtomic(...),
            self::union(...),
        );
    }

    public function namedObjectAtomic(string $class): TNamedObject
    {
        return new TNamedObject($class);
    }

    /**
     * @param non-empty-array<int|string, Union|Atomic> $properties
     */
    public function keyedArrayListAtomic(array $properties): TKeyedArray
    {
        return new TKeyedArray(
            properties: pipe(
                $properties,
                D\map(fn($i) => self::union($i)),
            ),
            is_list: true,
        );
    }

    /**
     * @param non-empty-array<int|string, Union|Atomic> $properties
     */
    public function keyedArrayList(array $properties): Union
    {
        return pipe(
            $properties,
            self::keyedArrayListAtomic(...),
            self::union(...),
        );
    }
}
