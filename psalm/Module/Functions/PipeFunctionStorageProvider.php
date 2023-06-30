<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Functions;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;
use Psalm\Type\Atomic\TCallable;

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
            L\map(fn(int $offset) => PsalmApi::$create->callableAtomic(
                params: pipe(
                    $templates->createTemplate("T{$offset}"),
                    PsalmApi::$create->param('input'),
                ),
                return: pipe(
                    $templates->createTemplate('T'.($offset + 1)),
                    PsalmApi::$create->union(...),
                ),
            )),
        );

        $storage = new DynamicFunctionStorage();

        $storage->params = pipe(
            $pipeCallables,
            L\mapKV(fn(int $offset, TCallable $callable) => pipe(
                $callable,
                PsalmApi::$create->param("fn_{$offset}"),
            )),
            L\prepend(pipe(
                $templates->createTemplate('T1'),
                PsalmApi::$create->param('pipe_input'),
            )),
        );

        $storage->return_type = pipe(
            $pipeCallables,
            L\last(...),
            O\map(fn(TCallable $fn) => $fn->return_type),
            O\getOrNull(...),
        );

        $storage->templates = pipe(
            range(start: 1, end: $argsCount),
            L\map(fn($offset) => "T{$offset}"),
            L\map($templates->createTemplate(...)),
        );

        return $storage;
    }
}
