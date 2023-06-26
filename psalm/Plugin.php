<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration;

use Fp4\PHP\PsalmIntegration\ArrayList\FromCallInference;
use Fp4\PHP\PsalmIntegration\Bindable\BindableGetReturnTypeProvider;
use Fp4\PHP\PsalmIntegration\Bindable\BindablePropertiesResolver;
use Fp4\PHP\PsalmIntegration\Bindable\BindFunctionStorageProvider;
use Fp4\PHP\PsalmIntegration\Bindable\LetFunctionStorageProvider;
use Fp4\PHP\PsalmIntegration\Option\FilterCallRefinement;
use Fp4\PHP\PsalmIntegration\Option\NoneConstInference;
use Fp4\PHP\PsalmIntegration\Option\SomeCallInference;
use Fp4\PHP\PsalmIntegration\Pipe\PipeFunctionStorageProvider;
use Fp4\PHP\PsalmIntegration\Psalm\DumpTypeHandler;
use Fp4\PHP\PsalmIntegration\Psalm\ExpectTypeHandler;
use Fp4\PHP\PsalmIntegration\Psalm\SuppressIssueHandler;
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
        if (class_exists(NoneConstInference::class)) {
            $registration->registerHooksFromClass(NoneConstInference::class);
        }
        if (class_exists(FromCallInference::class)) {
            $registration->registerHooksFromClass(FromCallInference::class);
        }
        if (class_exists(SomeCallInference::class)) {
            $registration->registerHooksFromClass(SomeCallInference::class);
        }
        if (class_exists(BindFunctionStorageProvider::class)) {
            $registration->registerHooksFromClass(BindFunctionStorageProvider::class);
        }
        if (class_exists(LetFunctionStorageProvider::class)) {
            $registration->registerHooksFromClass(LetFunctionStorageProvider::class);
        }
        if (class_exists(BindablePropertiesResolver::class)) {
            $registration->registerHooksFromClass(BindablePropertiesResolver::class);
        }
        if (class_exists(BindableGetReturnTypeProvider::class)) {
            $registration->registerHooksFromClass(BindableGetReturnTypeProvider::class);
        }
        if (class_exists(FilterCallRefinement::class)) {
            $registration->registerHooksFromClass(FilterCallRefinement::class);
        }
        if (class_exists(DumpTypeHandler::class)) {
            $registration->registerHooksFromClass(DumpTypeHandler::class);
        }
        if (class_exists(ExpectTypeHandler::class)) {
            $registration->registerHooksFromClass(ExpectTypeHandler::class);
        }
        if (class_exists(SuppressIssueHandler::class)) {
            $registration->registerHooksFromClass(SuppressIssueHandler::class);
        }
    }
}
