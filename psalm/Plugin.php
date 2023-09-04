<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration;

use Closure;
use Fp4\PHP\ArrayList as L;
use Fp4\PHP\PsalmIntegration\Module\ArrayDictionary\ArrayDictionaryModule;
use Fp4\PHP\PsalmIntegration\Module\ArrayList\ArrayListModule;
use Fp4\PHP\PsalmIntegration\Module\Bindable\BindableModule;
use Fp4\PHP\PsalmIntegration\Module\Combinator\CombinatorModule;
use Fp4\PHP\PsalmIntegration\Module\Either\EitherModule;
use Fp4\PHP\PsalmIntegration\Module\Option\OptionModule;
use Fp4\PHP\PsalmIntegration\Module\Pair\PairModule;
use Fp4\PHP\PsalmIntegration\Module\Psalm\PsalmModule;
use Fp4\PHP\PsalmIntegration\Module\Shape\ShapeModule;
use Fp4\PHP\PsalmIntegration\Module\Tuple\TupleModule;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Casts;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\CreateType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Types;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use function Fp4\PHP\Combinator\pipe;

/**
 * @api
 */
final class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        PsalmApi::$type = new Types();
        PsalmApi::$create = new CreateType();
        PsalmApi::$cast = new Casts();
        PsalmApi::$codebase = ProjectAnalyzer::$instance->getCodebase();

        $register = self::register($registration);

        pipe(
            L\from([
                new PsalmModule(),
                new OptionModule(),
                new ArrayListModule(),
                new ArrayDictionaryModule(),
                new CombinatorModule(),
                new BindableModule(),
                new ShapeModule(),
                new TupleModule(),
                new EitherModule(),
                new PairModule(),
            ]),
            L\tap(fn(RegisterPsalmHooks $hooks) => $hooks($register)),
        );

        $registration->addStubFile(__DIR__.'/Stub/NodeAbstract.phpstub');
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
