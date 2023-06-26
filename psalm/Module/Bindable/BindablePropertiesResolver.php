<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Bindable;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr\FuncCall;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\constVoid;
use function Fp4\PHP\Module\Functions\pipe;

final class BindablePropertiesResolver implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(self::inferBind($event), constNull(...));
    }

    /**
     * @return Option<void>
     */
    private static function inferBind(AfterExpressionAnalysisEvent $event): Option
    {
        return pipe(
            O\some($event->getExpr()),
            O\flatMap(Ev\proveOf(FuncCall::class)),
            O\filter(fn(FuncCall $call) => pipe(
                L\from(['Fp4\PHP\Module\Option\bind', 'Fp4\PHP\Module\Option\let']),
                L\contains($call->name->getAttribute('resolvedName')),
            )),
            O\flatMap(PsalmApi::$types->getExprType($event)),
            O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
            O\flatMap(Ev\proveOf(TClosure::class)),
            O\flatMap(self::inferClosure(...)),
            O\tap(PsalmApi::$types->setExprType($event->getExpr(), $event)),
            O\map(constVoid(...)),
        );
    }

    /**
     * @return Option<TClosure>
     */
    private static function inferClosure(TClosure $returnType): Option
    {
        return pipe(
            O\fromNullable($returnType->return_type),
            O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
            O\flatMap(Ev\proveOf(TGenericObject::class)),
            O\filter(fn(TGenericObject $option) => Option::class === $option->value),
            O\flatMap(fn(TGenericObject $option) => pipe(
                $option->type_params,
                L\first(...),
            )),
            O\flatMap(BindableFoldType::for(...)),
            O\flatMap(Ev\proveNonEmptyArray(...)),
            O\map(fn(array $properties) => $returnType->replace(
                params: $returnType->params,
                return_type: self::returnType($properties),
            )),
        );
    }

    /**
     * @param non-empty-array<Union> $properties
     */
    private static function returnType(array $properties): Union
    {
        $bindable = new Union([
            new TGenericObject(Bindable::class, [
                new Union([
                    new TObjectWithProperties($properties),
                ]),
            ]),
        ]);

        return new Union([
            new TGenericObject(Option::class, [$bindable]),
        ]);
    }
}
