<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\PsalmIntegration\Module\ArrayDictionary\ArrayDictionaryModule;
use Fp4\PHP\PsalmIntegration\Module\ArrayList\ArrayListModule;
use Fp4\PHP\PsalmIntegration\Module\Bindable\BindableModule;
use Fp4\PHP\PsalmIntegration\Module\Functions\FunctionsModule;
use Fp4\PHP\PsalmIntegration\Module\Option\OptionModule;
use Fp4\PHP\PsalmIntegration\Module\Psalm\PsalmModule;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Types;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use function Fp4\PHP\Module\Functions\pipe;

/**
 * @api
 */
final class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        PsalmApi::$types = new Types();
        PsalmApi::$codebase = ProjectAnalyzer::$instance->getCodebase();

        $register = self::register($registration);

        pipe(
            L\from([
                new PsalmModule(),
                new OptionModule(),
                new ArrayListModule(),
                new ArrayDictionaryModule(),
                new FunctionsModule(),
                new BindableModule(),
            ]),
            L\tap(fn(RegisterPsalmHooks $hooks) => $hooks($register)),
        );
    }

    /**
     * @return Closure(non-empty-list<string>): void
     */
    private static function register(RegistrationInterface $registration): Closure
    {
        return function(array $classes) use ($registration): void {
            pipe(
                $classes,
                L\filter(class_exists(...)),
                L\tap($registration->registerHooksFromClass(...)),
            );
        };
    }
}
