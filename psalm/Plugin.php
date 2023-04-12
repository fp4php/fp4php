<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration;

use Fp4\PHP\PsalmIntegration\ArrayList\FromIterableCallWidening;
use Fp4\PHP\PsalmIntegration\Bindable\BindablePropertiesResolver;
use Fp4\PHP\PsalmIntegration\Bindable\BindFunctionStorageProvider;
use Fp4\PHP\PsalmIntegration\Option\NoneConstWidening;
use Fp4\PHP\PsalmIntegration\Option\SomeCallWidening;
use Fp4\PHP\PsalmIntegration\Pipe\PipeFunctionStorageProvider;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Types;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        PsalmApi::$types = new Types();

        if (class_exists(PipeFunctionStorageProvider::class)) {
            $registration->registerHooksFromClass(PipeFunctionStorageProvider::class);
        }
        if (class_exists(NoneConstWidening::class)) {
            $registration->registerHooksFromClass(NoneConstWidening::class);
        }
        if (class_exists(FromIterableCallWidening::class)) {
            $registration->registerHooksFromClass(FromIterableCallWidening::class);
        }
        if (class_exists(SomeCallWidening::class)) {
            $registration->registerHooksFromClass(SomeCallWidening::class);
        }
        if (class_exists(BindFunctionStorageProvider::class)) {
            $registration->registerHooksFromClass(BindFunctionStorageProvider::class);
        }
        if (class_exists(BindablePropertiesResolver::class)) {
            $registration->registerHooksFromClass(BindablePropertiesResolver::class);
        }
    }
}
