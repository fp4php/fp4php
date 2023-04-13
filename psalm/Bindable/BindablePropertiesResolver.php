<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Bindable;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use Psalm\IssueBuffer;
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
        return pipe(
            self::inferBindPart($event),
            O\orElse(fn () => self::inferBind($event)),
            constNull(...),
        );
    }

    /**
     * @return Option<void>
     */
    private static function inferBind(AfterExpressionAnalysisEvent $event): Option
    {
        return pipe(
            O\some($event->getExpr()),
            O\flatMap(Ev\proveOf(FuncCall::class)),
            O\filter(fn (FuncCall $call) => 'Fp4\PHP\Module\Option\bind' === $call->name->getAttribute('resolvedName')),
            O\flatMap(PsalmApi::$types->getExprType($event)),
            O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
            O\flatMap(Ev\proveOf(TClosure::class)),
            O\flatMap(self::inferClosure(...)),
            O\tap(PsalmApi::$types->setType($event->getExpr(), $event)),
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
            O\filter(fn (TGenericObject $option) => $option->value === Option::class),
            O\flatMap(fn (TGenericObject $option) => pipe(
                $option->type_params,
                L\first(...),
            )),
            O\flatMap(self::foldBindableContext(...)),
            O\flatMap(function (array $properties) use ($returnType) {
                if ($properties === []) {
                    return O\none;
                }

                $bindable = new Union([
                    new TGenericObject(Bindable::class, [
                        new Union([
                            new TObjectWithProperties($properties),
                        ]),
                    ]),
                ]);

                return O\some($returnType->replace(
                    params: $returnType->params,
                    return_type: new Union([
                        new TGenericObject(Option::class, [$bindable]),
                    ]),
                ));
            }),
        );
    }

    /**
     * @return Option<void>
     */
    private static function inferBindPart(AfterExpressionAnalysisEvent $event): Option
    {
        $source = $event->getStatementsSource();
        $context = $event->getContext();

        return pipe(
            O\some($event->getExpr()),
            O\filter(fn () => Bindable::class !== $context->self),
            O\flatMap(Ev\proveOf(PropertyFetch::class)),
            O\flatMap(fn (PropertyFetch $expr) => pipe(
                $expr->name,
                Ev\proveOf(Identifier::class),
                O\map(fn (Identifier $i) => $i->toString()),
                O\flatMap(fn (string $property) => pipe(
                    $expr->var,
                    PsalmApi::$types->getExprType($event),
                    O\flatMap(self::foldBindableContext(...)),
                    O\flatMap(fn (array $context) => pipe(
                        $context,
                        D\get($property),
                        O\orElse(function () use ($expr, $source) {
                            IssueBuffer::maybeAdd(
                                e: PropertyIsNotDefinedInScope::create($expr, $source),
                                suppressed_issues: $source->getSuppressedIssues(),
                            );

                            return O\none;
                        }),
                    )),
                )),
            )),
            O\tap(PsalmApi::$types->setType($event->getExpr(), $event)),
            O\map(constVoid(...)),
        );
    }

    /**
     * @return Option<Union[]>
     */
    private static function foldBindableContext(Union $type): Option
    {
        return pipe(
            $type,
            PsalmApi::$types->asSingleAtomic(...),
            O\flatMap(Ev\proveOf(TGenericObject::class)),
            O\filter(fn (TGenericObject $bindable) => Bindable::class === $bindable->value),
            O\map(fn (TGenericObject $bindable) => pipe(
                O\fromNullable($bindable->extra_types[Bindable::class . '<object>'] ?? null),
                O\flatMap(Ev\proveOf(TGenericObject::class)),
                O\map(fn (TGenericObject $intersection) => [
                    ...self::getProperties($bindable),
                    ...self::getProperties($intersection),
                ]),
                O\getOrCall(fn () => self::getProperties($bindable)),
            )),
        );
    }

    /**
     * @return Union[]
     */
    private static function getProperties(TGenericObject $bindable): array
    {
        return pipe(
            $bindable->type_params,
            L\first(...),
            O\flatMap(PsalmApi::$types->asSingleAtomic(...)),
            O\flatMap(Ev\proveOf(TObjectWithProperties::class)),
            O\map(fn (TObjectWithProperties $object) => $object->properties),
            O\getOrCall(fn () => []),
        );
    }
}
