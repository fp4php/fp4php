<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\FunctionType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\FilterRefinement;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefineTypeParams;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Combinator\constNull;
use function Fp4\PHP\Module\Combinator\pipe;

final class PartitionCallRefinement implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event,
            FilterRefinement::refine(
                function: 'Fp4\PHP\Module\ArrayList\partition',
                getKeyType: O\none,
                getValType: self::getLeftType(...),
                toReturnType: fn(RefineTypeParams $refined, Union $original) => PsalmApi::$create->keyedArrayList([
                    PsalmApi::$create->list(pipe(
                        O\some($original),
                        O\flatMap(self::getLeftType(...)),
                        O\map(fn(Union $leftType) => pipe(
                            O\some($leftType),
                            O\flatMap(PsalmApi::$type->remove($refined->value)),
                            O\getOrElse($leftType),
                        )),
                        O\getOrCall(PsalmApi::$create->never(...)),
                    )),
                    PsalmApi::$create->list($refined->value),
                ]),
                type: FunctionType::Value,
            ),
            constNull(...),
        );
    }

    /**
     * @return Option<Union>
     */
    private static function getLeftType(Union $original): Option
    {
        return pipe(
            O\some($original),
            O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TKeyedArray::class)),
            O\filter(fn(TKeyedArray $separated) => $separated->is_list),
            O\flatMap(fn(TKeyedArray $separated) => Ev\proveNonEmptyList($separated->properties)),
            O\flatMap(L\first(...)),
            O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TKeyedArray::class)),
            O\filter(fn(TKeyedArray $left) => $left->isGenericList()),
            O\map(fn(TKeyedArray $left) => $left->getGenericValueType()),
        );
    }
}
