<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Bindable;

use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\Evidence as Ev;
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

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class BindablePropertiesResolver implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $source = $event->getStatementsSource();
        $context = $event->getContext();

        return pipe(
            O\some($event->getExpr()),
            O\filter(fn () => Bindable::class === $context->self),
            Ev\proveOf(PropertyFetch::class),
            O\flatMap(fn (PropertyFetch $expr) => pipe(
                $expr->name,
                Ev\proveOf(Identifier::class),
                O\map(fn (Identifier $i) => $i->toString()),
                O\flatMap(fn (string $property) => pipe(
                    $expr->var,
                    PsalmApi::$types->getExprType($event),
                    O\flatMap(self::foldBindableContext(...)),
                    O\flatMap(D\get($property)),
                    O\orElse(function () use ($expr, $source) {
                        IssueBuffer::maybeAdd(
                            e: PropertyIsNotDefinedInScope::create($expr, $source),
                            suppressed_issues: $source->getSuppressedIssues(),
                        );

                        return O\none;
                    }),
                )),
            )),
            O\tap(PsalmApi::$types->setType($event->getExpr(), $event)),
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
            O\flatMap(Ev\proveOf(TGenericObject::class)),
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
