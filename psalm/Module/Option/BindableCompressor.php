<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Option;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindablePropertiesCompressor;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
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
        $unpack = fn(Union $from): Option => pipe(
            O\some($from),
            O\flatMap(PsalmApi::$types->asSingleAtomicOf(TGenericObject::class)),
            O\filter(fn(TGenericObject $generic) => Option::class === $generic->value),
        );

        return pipe(
            $event,
            BindablePropertiesCompressor::compress(
                functions: [
                    'Fp4\PHP\Module\Option\bind',
                    'Fp4\PHP\Module\Option\let',
                ],
                unpack: fn(Union $original) => pipe(
                    $unpack($original),
                    O\flatMap(fn(TGenericObject $option) => L\first($option->type_params)),
                ),
                pack: function(array $properties, Union $original) use ($unpack) {
                    $bindable = new Union([
                        new TGenericObject(Bindable::class, [
                            new Union([
                                new TObjectWithProperties($properties),
                            ]),
                        ]),
                    ]);

                    return pipe(
                        $unpack($original),
                        O\map(fn(TGenericObject $option) => $option->setTypeParams([$bindable])),
                        O\map(PsalmApi::$types->asUnion(...)),
                    );
                },
            ),
            constNull(...),
        );
    }
}
