<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\constVoid;
use function Fp4\PHP\Combinator\pipe;

final class Narrowing
{
    /**
     * @param non-empty-list<non-empty-string> $names
     * @return O\Option<void>
     */
    public static function assertNarrowed(AfterExpressionAnalysisEvent $event, array $names): O\Option
    {
        return pipe(
            O\some($event->getExpr()),
            O\filterOf(FuncCall::class),
            O\filter(fn(FuncCall $c) => pipe(
                $names,
                L\contains($c->name->getAttribute('resolvedName')),
            )),
            O\filter(fn(FuncCall $c) => !$c->isFirstClassCallable()),
            O\flatMap(fn(FuncCall $c) => pipe(
                L\fromIterable($c->getArgs()),
                L\first(...),
                O\map(fn(Arg $arg) => $arg->value),
            )),
            O\flatMap(PsalmApi::$type->get($event)),
            O\filter(fn(Union $type) => !self::isLiteral($type)),
            O\tap(fn(Union $type) => self::raiseIssueIfTypeIsNotLiteral($type, $event)),
            O\map(constVoid(...)),
        );
    }

    private static function isLiteral(Union $type): bool
    {
        $isLiteralOrNull = fn(): O\Option => pipe(
            O\some($type),
            O\filter(fn($t) => $t->containsAnyLiteral() || $t->isNull()),
            O\map(fn() => true),
        );

        $isNonGenericKeyedArray = fn(): O\Option => pipe(
            O\some($type),
            O\flatMap(PsalmApi::$cast->toSingleAtomicOf(TKeyedArray::class)),
            O\filter(fn(TKeyedArray $keyed) => !$keyed->isGenericList()),
            O\map(fn() => true),
        );

        return pipe(
            O\first($isLiteralOrNull, $isNonGenericKeyedArray),
            O\getOrElse(false),
        );
    }

    private static function raiseIssueIfTypeIsNotLiteral(Union $type, AfterExpressionAnalysisEvent $event): void
    {
        $source = $event->getStatementsSource();

        IssueBuffer::maybeAdd(
            e: new InvalidArgument(
                message: "Type {$type->getId()} is not literal type.",
                code_location: new CodeLocation($source, $event->getExpr()),
            ),
            suppressed_issues: $source->getSuppressedIssues(),
        );
    }
}
