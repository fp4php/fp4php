<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Either;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindableFunctionBuilder;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindLetType;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;

use function Fp4\PHP\Module\Combinator\pipe;

final class LetFunctionStorageProvider implements DynamicFunctionStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Module\Either\let'),
        ];
    }

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage
    {
        $builderForEither = new BindableBuilder(
            templates: $event->getTemplateProvider(),
            type: BindLetType::LET,
        );

        return pipe(
            $event,
            BindableFunctionBuilder::buildStorage(with: $builderForEither),
            O\getOrNull(...),
        );
    }
}
