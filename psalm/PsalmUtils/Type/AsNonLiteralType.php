<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\ArrayList as L;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Combinator\pipe;

final class AsNonLiteralType
{
    public static function transform(Union $type, AsNonLiteralTypeConfig $config): Union
    {
        return new Union(pipe(
            D\fromNonEmpty($type->getAtomicTypes()),
            D\map(fn(Type\Atomic $a) => match (true) {
                $a instanceof TTrue, $a instanceof TFalse => new TBool(),
                $a instanceof TLiteralString => new TString(),
                $a instanceof TLiteralInt, $a instanceof TIntRange => new TInt(),
                $a instanceof TLiteralFloat => new TFloat(),
                $config->transformNested => match (true) {
                    $a instanceof TKeyedArray => match (true) {
                        !$a->isGenericList() && $config->preserveKeyedArrayShape => $a->setProperties(pipe(
                            D\fromNonEmpty($a->properties),
                            D\map(fn(Union $property) => self::transform(
                                type: $property,
                                config: $config->stopTransformNested(),
                            )),
                        )),
                        $a->is_list && $a->isNonEmpty() => pipe(
                            self::transform($a->getGenericValueType(), $config->stopTransformNested()),
                            Type::getNonEmptyListAtomic(...),
                        ),
                        $a->is_list => pipe(
                            self::transform($a->getGenericValueType(), $config->stopTransformNested()),
                            Type::getListAtomic(...),
                        ),
                        default => new TArray([
                            self::transform($a->getGenericKeyType(), $config->stopTransformNested()),
                            self::transform($a->getGenericValueType(), $config->stopTransformNested()),
                        ]),
                    },
                    $a instanceof TNonEmptyArray => new TNonEmptyArray([
                        self::transform($a->type_params[0], $config->stopTransformNested()),
                        self::transform($a->type_params[1], $config->stopTransformNested()),
                    ]),
                    $a instanceof TArray => new TArray([
                        self::transform($a->type_params[0], $config->stopTransformNested()),
                        self::transform($a->type_params[1], $config->stopTransformNested()),
                    ]),
                    $a instanceof TGenericObject => $a->setTypeParams(pipe(
                        L\fromNonEmpty($a->type_params),
                        L\map(fn(Union $typeParam) => self::transform(
                            type: $typeParam,
                            config: $config->stopTransformNested(),
                        )),
                    )),
                    default => $a,
                },
                default => $a,
            }),
        ));
    }
}
