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
use Psalm\Type\Union;

use function Fp4\PHP\Module\Combinator\constNull;
use function Fp4\PHP\Module\Combinator\pipe;

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
                unpack: fn(Union $original) => pipe(
                    O\some($original),
                    O\flatMap(PsalmApi::$cast->toSingleGenericObjectOf(Option::class)),
                    O\flatMap(fn(TGenericObject $option) => L\first($option->type_params)),
                ),
                pack: fn(array $properties) => pipe(
                    PsalmApi::$create->objectWithProperties($properties),
                    PsalmApi::$create->genericObject(Bindable::class),
                    PsalmApi::$create->genericObject(Option::class),
                    O\some(...),
                ),
            ),
            constNull(...),
        );
    }
}
