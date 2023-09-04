<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Combinator;

use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Evidence as Ev;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;

use function count;
use function Fp4\PHP\Combinator\pipe;

final class TupledFunctionStorageProvider implements DynamicFunctionStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Combinator\tupled'),
        ];
    }

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage
    {
        $templateProvider = $event->getTemplateProvider();

        return pipe(
            L\fromIterable($event->getArgs()),
            L\first(...),
            O\map(fn(Arg $arg) => $arg->value),
            O\filterOf([Closure::class, ArrowFunction::class]),
            O\tap(fn($fn) => $fn->setAttribute('signatureDynamicallyProvided', true)),
            O\map(fn($closure) => pipe(
                range(start: 0, end: count($closure->params)),
                L\map(fn($offset) => $templateProvider->createTemplate("T{$offset}")),
            )),
            O\flatMap(fn($templates) => pipe(
                O\bindable(),
                O\bind(
                    templates: fn() => O\some($templates),
                    tuple: fn() => pipe($templates, L\init(...), Ev\proveNonEmptyList(...)),
                    return: fn() => pipe($templates, L\last(...)),
                ),
            )),
            O\map(function($i) {
                $storage = new DynamicFunctionStorage();

                $storage->params = [
                    pipe(
                        PsalmApi::$create->closure(
                            params: pipe(
                                $i->tuple,
                                L\mapKV(fn($offset, $type) => PsalmApi::$create->param("p{$offset}")($type)),
                            ),
                            return: $i->return,
                        ),
                        PsalmApi::$create->param('acceptsDeconstructedTuple'),
                    ),
                ];

                $storage->return_type = PsalmApi::$create->closure(
                    params: pipe(
                        PsalmApi::$create->keyedArrayList($i->tuple),
                        PsalmApi::$create->param('acceptsTuple'),
                    ),
                    return: $i->return,
                );

                $storage->templates = $i->templates;

                return $storage;
            }),
            O\getOrNull(...),
        );
    }
}
