<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\Bindable;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindablePropertiesCompressor;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TKeyedArray;
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
                    'Fp4\PHP\ArrayList\bind',
                    'Fp4\PHP\ArrayList\let',
                ],
                unpack: fn(Union $original) => pipe(
                    O\some($original),
                    O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TKeyedArray::class)),
                    O\filter(fn(TKeyedArray $generic) => $generic->isGenericList()),
                    O\map(fn(TKeyedArray $option) => $option->getGenericValueType()),
                ),
                pack: fn(array $properties) => pipe(
                    PsalmApi::$create->objectWithProperties($properties),
                    PsalmApi::$create->genericObject(Bindable::class),
                    PsalmApi::$create->list(...),
                    O\some(...),
                ),
            ),
            constNull(...),
        );
    }
}
