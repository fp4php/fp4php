<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Functions;

use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;

final class CtorFunctionReturnTypeProvider implements FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [strtolower('Fp4\PHP\Module\Functions\ctor')];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        return pipe(
            L\first($event->getCallArgs()),
            O\map(fn(Arg $arg) => $arg->value),
            O\flatMap(PsalmApi::$type->get($event)),
            O\flatMap(PsalmApi::$cast->toSingleAtomic(...)),
            O\filterOf(TLiteralClassString::class),
            O\map(fn(TLiteralClassString $class) => PsalmApi::$create->closure(
                params: pipe(
                    O\fromNullable(PsalmApi::$codebase->classlikes->getStorageFor($class->value)),
                    O\flatMap(fn(ClassLikeStorage $storage) => pipe(
                        $storage->methods,
                        D\get('__construct'),
                    )),
                    O\map(fn(MethodStorage $storage) => $storage->params),
                    O\getOrElse([]),
                ),
                return: PsalmApi::$create->namedObject($class->value),
            )),
            O\getOrNull(...),
        );
    }
}
