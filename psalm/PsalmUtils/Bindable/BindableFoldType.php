<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable;

use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Bindable;
use Fp4\PHP\Option as O;
use Fp4\PHP\Option\Option;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\pipe;

final class BindableFoldType
{
    /**
     * @return Option<Union[]>
     */
    public static function for(Union $type): Option
    {
        return pipe(
            $type,
            PsalmApi::$cast->toSingleAtomic(...),
            O\filterOf(TGenericObject::class),
            O\filter(fn(TGenericObject $bindable) => Bindable::class === $bindable->value),
            O\map(fn(TGenericObject $bindable) => pipe(
                O\fromNullable($bindable->extra_types[Bindable::class.'<object>'] ?? null),
                O\filterOf(TGenericObject::class),
                O\map(fn(TGenericObject $intersection) => [
                    ...self::getProperties($bindable),
                    ...self::getProperties($intersection),
                ]),
                O\getOrCall(fn() => self::getProperties($bindable)),
            )),
        );
    }

    /**
     * @return Union[]
     */
    private static function getProperties(TGenericObject $bindable): array
    {
        return pipe(
            $bindable->type_params,
            L\first(...),
            O\flatMap(PsalmApi::$cast->toSingleAtomic(...)),
            O\filterOf(TObjectWithProperties::class),
            O\map(fn(TObjectWithProperties $object) => $object->properties),
            O\getOrCall(fn() => []),
        );
    }
}
