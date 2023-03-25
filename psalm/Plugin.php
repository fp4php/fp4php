<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration;

use Fp4\PHP\PsalmIntegration\Option\NoneConstWidening;
use Fp4\PHP\PsalmIntegration\Pipe\PipeFunctionStorageProvider;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        if (class_exists(PipeFunctionStorageProvider::class)) {
            $registration->registerHooksFromClass(PipeFunctionStorageProvider::class);
        }
        if (class_exists(NoneConstWidening::class)) {
            $registration->registerHooksFromClass(NoneConstWidening::class);
        }
    }
}
