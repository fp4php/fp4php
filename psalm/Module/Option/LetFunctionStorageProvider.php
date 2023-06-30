<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Option;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindableFunctionBuilder;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindLetType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\SingleTypeParameterBindableBuilder;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;
use Psalm\Type\Union;

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
        $builderForOption = new SingleTypeParameterBindableBuilder(
            templates: $event->getTemplateProvider(),
            type: BindLetType::LET,
            liftF: fn(Union $bindable) => pipe($bindable, PsalmApi::$create->genericObject(Option::class)),
        );

        return pipe(
            $event,
            BindableFunctionBuilder::buildStorage(with: $builderForOption),
            O\getOrNull(...),
        );
    }
}
