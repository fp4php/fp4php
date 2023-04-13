<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Bindable;

use Fp4\PHP\Module\Option as O;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;

use function Fp4\PHP\Module\Functions\pipe;

final class BindFunctionStorageProvider implements DynamicFunctionStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Module\Option\bind'),
        ];
    }

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage
    {
        return pipe(
            $event,
            BindFunctionBuilder::buildStorage(...),
            O\getOrNull(...),
        );
    }
}
