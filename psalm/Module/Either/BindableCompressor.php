<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Either;

use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Bindable;
use Fp4\PHP\Either\Either;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindablePropertiesCompressor;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class BindableCompressor implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event,
            BindablePropertiesCompressor::compress(
                functions: [
                    'Fp4\PHP\Either\bind',
                    'Fp4\PHP\Either\let',
                ],
                unpack: fn(Union $original) => pipe(
                    O\some($original),
                    O\flatMap(PsalmApi::$cast->toSingleGenericObjectOf(Either::class)),
                    O\flatMap(fn(TGenericObject $option) => L\second($option->type_params)),
                ),
                pack: fn(array $properties, Union $original) => pipe(
                    [
                        pipe(
                            O\some($original),
                            O\flatMap(PsalmApi::$cast->toSingleGenericObjectOf(Either::class)),
                            O\flatMap(fn(TGenericObject $either) => L\first($either->type_params)),
                            O\getOrCall(PsalmApi::$create->never(...)),
                        ),
                        pipe(
                            PsalmApi::$create->objectWithProperties($properties),
                            PsalmApi::$create->genericObject(Bindable::class),
                        ),
                    ],
                    PsalmApi::$create->genericObject(Either::class),
                    O\some(...),
                ),
            ),
            constNull(...),
        );
    }
}
