<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Combinator\pipe;
use function is_array;

final class Casts
{
    public function toNonLiteralType(Union $type, AsNonLiteralTypeConfig $config = new AsNonLiteralTypeConfig()): Union
    {
        return AsNonLiteralType::transform($type, $config);
    }

    /**
     * @return Option<Atomic>
     */
    public function toSingleAtomic(Union $type): Option
    {
        return pipe(
            O\some($type),
            O\filter(fn(Union $t) => $t->isSingle()),
            O\map(fn(Union $t) => $t->getSingleAtomic()),
        );
    }

    /**
     * @template TAtomic of Atomic
     *
     * @param class-string<TAtomic>|non-empty-list<class-string<TAtomic>> $class
     * @return Closure(Union): Option<TAtomic>
     */
    public function toSingleAtomicOf(string|array $class): Closure
    {
        return fn(Union $type) => pipe(
            O\some($type),
            O\flatMap($this->toSingleAtomic(...)),
            O\filterOf($class),
        );
    }

    /**
     * @param class-string|non-empty-list<class-string> $class
     * @return Closure(Union): Option<TGenericObject>
     */
    public function toSingleGenericObjectOf(string|array $class): Closure
    {
        return fn(Union $type) => pipe(
            O\some($type),
            O\flatMap($this->toSingleAtomic(...)),
            O\filterOf(TGenericObject::class),
            O\filter(fn(TGenericObject $object) => pipe(
                is_array($class) ? $class : [$class],
                L\contains($object->value),
            )),
        );
    }
}
