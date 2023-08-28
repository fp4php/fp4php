<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayDictionary;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\FilterRefinement;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefinementType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement\RefineTypeParams;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class PartitionCallRefinement implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event,
            FilterRefinement::refine(
                function: 'Fp4\PHP\Module\ArrayDictionary\partition',
                getKeyType: O\none,
                getValType: self::getLeftValueType(...),
                toReturnType: fn(RefineTypeParams $refined, Union $original) => PsalmApi::$create->keyedArrayList([
                    PsalmApi::$create->array(
                        pipe(
                            self::getLeftKeyType($original),
                            O\getOrCall(PsalmApi::$create->never(...)),
                        ),
                        pipe(
                            self::getLeftValueType($original),
                            O\map(self::removeType($refined->value)),
                            O\getOrCall(PsalmApi::$create->never(...)),
                        ),
                    ),
                    PsalmApi::$create->array(
                        pipe(
                            self::getLeftKeyType($original),
                            O\getOrCall(PsalmApi::$create->never(...)),
                        ),
                        $refined->value,
                    ),
                ]),
                type: RefinementType::KeyValue,
            ),
            constNull(...),
        );
    }

    /**
     * @return Closure(Union $from): Union
     */
    private static function removeType(Union $remove): Closure
    {
        return fn(Union $from) => pipe($from, PsalmApi::$type->remove($remove), O\getOrElse($from));
    }

    /**
     * @return Option<TArray>
     */
    private static function getArray(Union $original): Option
    {
        return pipe(
            O\some($original),
            O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TKeyedArray::class)),
            O\filter(fn(TKeyedArray $separated) => $separated->is_list),
            O\flatMap(fn(TKeyedArray $separated) => Ev\proveNonEmptyList($separated->properties)),
            O\flatMap(L\first(...)),
            O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TArray::class)),
        );
    }

    /**
     * @return Option<Union>
     */
    private static function getLeftKeyType(Union $original): Option
    {
        return pipe(
            self::getArray($original),
            O\map(fn(TArray $left) => $left->type_params[0]),
        );
    }

    /**
     * @return Option<Union>
     */
    private static function getLeftValueType(Union $original): Option
    {
        return pipe(
            self::getArray($original),
            O\map(fn(TArray $left) => $left->type_params[1]),
        );
    }
}
