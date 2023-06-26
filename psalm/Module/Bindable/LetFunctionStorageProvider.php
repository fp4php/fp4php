<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Bindable;

use Fp4\PHP\Module\Option as O;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;

use function Fp4\PHP\Module\Functions\pipe;

final class LetFunctionStorageProvider implements DynamicFunctionStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Module\Option\let'),
        ];
    }

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage
    {
        return pipe(
            BindableFunctionBuilder::buildStorage($event, BindLetType::LET),
            O\getOrNull(...),
        );
    }
}
