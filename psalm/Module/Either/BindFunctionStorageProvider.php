<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Either;

use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindableFunctionBuilder;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindLetType;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;

use function Fp4\PHP\Combinator\pipe;

final class BindFunctionStorageProvider implements DynamicFunctionStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Either\bind'),
        ];
    }

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage
    {
        $builderForEither = new BindableBuilder(
            templates: $event->getTemplateProvider(),
            type: BindLetType::BIND,
        );

        return pipe(
            $event,
            BindableFunctionBuilder::buildStorage(with: $builderForEither),
            O\getOrNull(...),
        );
    }
}
