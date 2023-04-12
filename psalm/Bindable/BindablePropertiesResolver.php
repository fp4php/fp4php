<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Bindable;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Evidence\proveOf;
use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class BindablePropertiesResolver implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $expr = $event->getExpr();
        $source = $event->getStatementsSource();
        $context = $event->getContext();

        if (Bindable::class === $context->self) {
            return null;
        }

        if (!$expr instanceof PropertyFetch) {
            return null;
        }

        return pipe(
            $expr->var,
            PsalmApi::$types->getExprType($event),
            O\flatMap(self::foldBindableContext(...)),
            O\flatMap(fn (array $ctx) => pipe(
                $expr->name,
                proveOf(Identifier::class),
                O\map(fn (Identifier $i) => $i->toString()),
                O\flatMap(function (string $property) use ($ctx, $expr, $source) {
                    if (!isset($ctx[$property])) {
                        IssueBuffer::maybeAdd(
                            PropertyIsNotDefinedInScope::create($expr, $source),
                            $source->getSuppressedIssues(),
                        );

                        return O\none;
                    }

                    return O\some($ctx[$property]);
                }),
            )),
            O\tap(PsalmApi::$types->setType($expr, $event)),
            constNull(...),
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
            O\flatMap(proveOf(TGenericObject::class)),
            O\filter(fn (TGenericObject $bindable) => Bindable::class === $bindable->value),
            O\map(fn (TGenericObject $bindable) => pipe(
                O\fromNullable($bindable->extra_types['object'] ?? null),
                O\map(fn (Atomic $intersection) => [
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
    private static function getProperties(Atomic $bindable): array
    {
        return $bindable instanceof TObjectWithProperties
            ? $bindable->properties
            : [];
    }
}
