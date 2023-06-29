<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Option;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindablePropertiesCompressor;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Union;
use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class BindableCompressor implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event,
            BindablePropertiesCompressor::compress(
                functions: [
                    'Fp4\PHP\Module\Option\bind',
                    'Fp4\PHP\Module\Option\let',
                ],
                extractBindable: fn(TGenericObject $from) => pipe(
                    O\when(Option::class === $from->value, fn() => $from),
                    O\flatMap(fn(TGenericObject $option) => L\first($option->type_params)),
                ),
                packBindable: function(array $properties, TGenericObject $original) {
                    $bindable = new Union([
                        new TGenericObject(Bindable::class, [
                            new Union([
                                new TObjectWithProperties($properties),
                            ]),
                        ]),
                    ]);

                    return new Union([
                        $original->setTypeParams([$bindable]),
                    ]);
                },
            ),
            constNull(...),
        );
    }
}
