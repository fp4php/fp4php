<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Pipe;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\DynamicTemplateProvider;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Union;

use function count;
use function Fp4\PHP\Module\Functions\pipe;

final class PipeFunctionStorageProvider implements DynamicFunctionStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Module\Functions\pipe'),
        ];
    }

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage
    {
        $templates = $event->getTemplateProvider();
        $argsCount = count($event->getArgs());

        if ($argsCount < 1) {
            return null;
        }

        $pipeCallables = pipe(
            range(start: 1, end: $argsCount - 1),
            L\map(self::createABCallable($templates)),
        );

        $storage = new DynamicFunctionStorage();

        $storage->params = pipe(
            $pipeCallables,
            L\mapKV(fn (int $offset, TCallable $callable) => self::createParam(
                name: "fn_{$offset}",
                type: $callable,
            )),
            L\prepend(self::createParam(
                name: 'pipe_input',
                type: $templates->createTemplate('T1'),
            )),
        );

        $storage->return_type = pipe(
            $pipeCallables,
            L\last(),
            O\map(fn (TCallable $fn) => $fn->return_type),
            O\getOrNull(),
        );

        $storage->templates = pipe(
            range(start: 1, end: $argsCount),
            L\map(fn ($offset) => "T{$offset}"),
            L\map($templates->createTemplate(...)),
        );

        return $storage;
    }

    /**
     * @return Closure(int $offset): TCallable
     */
    private static function createABCallable(DynamicTemplateProvider $templates): Closure
    {
        return fn (int $offset) => new TCallable(
            value: 'callable',
            params: [
                self::createParam(
                    name: 'input',
                    type: $templates->createTemplate("T{$offset}"),
                ),
            ],
            return_type: new Union([
                $templates->createTemplate('T'.($offset + 1)),
            ]),
        );
    }

    private static function createParam(string $name, Atomic $type): FunctionLikeParameter
    {
        return new FunctionLikeParameter(
            name: $name,
            by_ref: false,
            type: new Union([$type]),
        );
    }
}
