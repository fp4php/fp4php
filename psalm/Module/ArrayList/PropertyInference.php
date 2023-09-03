<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Property\PropertyInferenceHandler;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TKeyedArray;
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
                function: 'Fp4\PHP\ArrayList\property',
                getKind: fn(Union $kind) => pipe($kind, PsalmApi::$cast->toSingleAtomicOf(TKeyedArray::class)),
                getKindParam: fn(TKeyedArray $list) => O\some($list->getGenericValueType()),
                mapKindParam: fn(TKeyedArray $_, Union $property) => PsalmApi::$create->list($property),
            ),
            constNull(...),
        );
    }
}
