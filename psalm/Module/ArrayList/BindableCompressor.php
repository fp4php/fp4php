<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindablePropertiesCompressor;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TKeyedArray;
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
            O\flatMap(PsalmApi::$types->asSingleAtomicOf(TKeyedArray::class)),
            O\filter(fn(TKeyedArray $generic) => $generic->isGenericList()),
        );

        return pipe(
            $event,
            BindablePropertiesCompressor::compress(
                functions: [
                    'Fp4\PHP\Module\ArrayList\bind',
                    'Fp4\PHP\Module\ArrayList\let',
                ],
                unpack: fn(Union $original) => pipe(
                    $unpack($original),
                    O\map(fn(TKeyedArray $option) => $option->getGenericValueType()),
                ),
                pack: function(array $properties) {
                    $bindable = new Union([
                        new TGenericObject(Bindable::class, [
                            new Union([
                                new TObjectWithProperties($properties),
                            ]),
                        ]),
                    ]);

                    return pipe(
                        O\some(Type::getListAtomic($bindable)),
                        O\map(PsalmApi::$types->asUnion(...)),
                    );
                },
            ),
            constNull(...),
        );
    }
}
