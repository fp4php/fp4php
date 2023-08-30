<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Arg;
use PhpParser\Node\Identifier;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;

use function Fp4\PHP\Module\Combinator\pipe;

final class BindableFunctionBuilder
{
    /**
     * @return Closure(DynamicFunctionStorageProviderEvent $event): Option<DynamicFunctionStorage>
     */
    public static function buildStorage(BindableBuilder $with): Closure
    {
        return function(DynamicFunctionStorageProviderEvent $event) use ($with) {
            $args = $event->getArgs();

            if (empty($args)) {
                return O\none;
            }

            return pipe(
                $args,
                L\traverseOption(fn(Arg $arg) => pipe(
                    O\some($arg->name),
                    O\filterOf(Identifier::class),
                    O\map(fn(Identifier $identifier) => $identifier->toString()),
                    O\map($with->getNextFunction(...)),
                )),
                O\map(function(array $params) use ($with) {
                    $storage = new DynamicFunctionStorage();
                    $storage->params = $params;
                    $storage->templates = $with->getTemplates();
                    $storage->return_type = $with->getReturnType();

                    return $storage;
                }),
            );
        };
    }
}
