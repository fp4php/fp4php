<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Either;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\FunctionType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\FilterRefinement;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefineTypeParams;
use Fp4\PHP\Type\Either;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class FilterOrElseCallRefinement implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event,
            FilterRefinement::refine(
                function: 'Fp4\PHP\Module\Either\filterOrElse',
                getKeyType: O\none,
                getValType: fn(Union $inferred) => pipe(
                    O\some($inferred),
                    O\flatMap(PsalmApi::$cast->toSingleGenericObjectOf(Either::class)),
                    O\flatMap(fn(TGenericObject $either) => L\second($either->type_params)),
                ),
                toReturnType: fn(RefineTypeParams $refined, Union $either) => pipe(
                    O\some($either),
                    O\flatMap(PsalmApi::$cast->toSingleGenericObjectOf(Either::class)),
                    O\flatMap(fn(TGenericObject $either) => L\first($either->type_params)),
                    O\getOrCall(PsalmApi::$create->never(...)),
                    L\singleton(...),
                    L\append($refined->value),
                    PsalmApi::$create->genericObject(Either::class),
                ),
                type: FunctionType::Value,
            ),
            constNull(...),
        );
    }
}
