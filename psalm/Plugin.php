<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration;

use Fp4\PHP\PsalmIntegration\ArrayList\FromCallWidening;
use Fp4\PHP\PsalmIntegration\ArrayList\FromLiteralCallValidator;
use Fp4\PHP\PsalmIntegration\Bindable\BindableGetReturnTypeProvider;
use Fp4\PHP\PsalmIntegration\Bindable\BindablePropertiesResolver;
use Fp4\PHP\PsalmIntegration\Bindable\BindFunctionStorageProvider;
use Fp4\PHP\PsalmIntegration\Option\FilterRefinement;
use Fp4\PHP\PsalmIntegration\Option\NoneConstWidening;
use Fp4\PHP\PsalmIntegration\Option\SomeCallWidening;
use Fp4\PHP\PsalmIntegration\Pipe\PipeFunctionStorageProvider;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Types;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        PsalmApi::$types = new Types();
        PsalmApi::$codebase = ProjectAnalyzer::$instance->getCodebase();

        if (class_exists(PipeFunctionStorageProvider::class)) {
            $registration->registerHooksFromClass(PipeFunctionStorageProvider::class);
        }
        if (class_exists(NoneConstWidening::class)) {
            $registration->registerHooksFromClass(NoneConstWidening::class);
        }
        if (class_exists(FromCallWidening::class)) {
            $registration->registerHooksFromClass(FromCallWidening::class);
        }
        if (class_exists(FromLiteralCallValidator::class)) {
            $registration->registerHooksFromClass(FromLiteralCallValidator::class);
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
        if (class_exists(BindableGetReturnTypeProvider::class)) {
            $registration->registerHooksFromClass(BindableGetReturnTypeProvider::class);
        }
        if (class_exists(FilterRefinement::class)) {
            $registration->registerHooksFromClass(FilterRefinement::class);
        }
    }
}
