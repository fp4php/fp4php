<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Fp4\PHP\Module\ArrayList as L;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;

final class AsNonLiteralType
{
    public static function transform(Union $type): Union
    {
        return new Union(pipe(
            L\fromIterable($type->getAtomicTypes()),
            L\map(fn (Type\Atomic $a) => match (true) {
                $a instanceof TTrue, $a instanceof TFalse => new TBool(),
                $a instanceof TLiteralClassString => new TClassString(),
                $a instanceof TLiteralString => empty($a->value)
                    ? new TString()
                    : new TNonEmptyString(),
                $a instanceof TLiteralInt, $a instanceof TIntRange => new TInt(),
                $a instanceof TLiteralFloat => new TFloat(),
                $a instanceof TKeyedArray => match (true) {
                    self::isNonEmptyList($a) => Type::getNonEmptyListAtomic(
                        self::transform($a->getGenericValueType()),
                    ),
                    self::isPossiblyEmptyList($a) => Type::getListAtomic(
                        self::transform($a->getGenericValueType()),
                    ),
                    default => new TNonEmptyArray([
                        self::transform($a->getGenericKeyType()),
                        self::transform($a->getGenericValueType()),
                    ]),
                },
                $a instanceof TNonEmptyArray => new TNonEmptyArray([
                    self::transform($a->type_params[0]),
                    self::transform($a->type_params[1]),
                ]),
                $a instanceof TArray => new TArray([
                    self::transform($a->type_params[0]),
                    self::transform($a->type_params[1]),
                ]),
                $a instanceof TGenericObject => new TGenericObject(
                    $a->value,
                    pipe(
                        L\fromIterable($a->type_params),
                        L\map(self::transform(...)),
                    ),
                ),
                default => $a,
            }),
        ));
    }

    private static function isPossiblyEmptyList(TKeyedArray $keyed): bool
    {
        if (isset($keyed->properties[0])
            && $keyed->fallback_params
            && $keyed->properties[0]->equals($keyed->fallback_params[1], true, true, false)
        ) {
            return $keyed->properties[0]->possibly_undefined;
        }

        return false;
    }

    private static function isNonEmptyList(TKeyedArray $keyed): bool
    {
        if (isset($keyed->properties[0])
            && $keyed->fallback_params
            && $keyed->properties[0]->equals($keyed->fallback_params[1], true, true, false)
        ) {
            return !$keyed->properties[0]->possibly_undefined;
        }

        return $keyed->is_list;
    }
}
