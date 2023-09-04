<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayDictionary;

use Fp4\PHP\Option as O;
use Fp4\PHP\Pair as P;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Property\PropertyInferenceHandler;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class PropertyInference implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event,
            PropertyInferenceHandler::handle(
                function: 'Fp4\PHP\ArrayDictionary\property',
                getKind: fn(Union $type) => pipe($type, PsalmApi::$cast->toSingleAtomicOf(TArray::class)),
                getKindParam: fn(TArray $kind) => pipe(
                    P\right($kind->type_params),
                    O\some(...),
                ),
                mapKindParam: fn(TArray $kind, Union $property) => PsalmApi::$create->union(
                    $kind instanceof TNonEmptyArray
                        ? PsalmApi::$create->nonEmptyArray(P\left($kind->type_params), $property)
                        : PsalmApi::$create->array(P\left($kind->type_params), $property),
                ),
            ),
            constNull(...),
        );
    }
}
