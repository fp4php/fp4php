<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\ArrayList;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Evidence\proveOf;
use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class FromLiteralCallValidator implements AfterExpressionAnalysisInterface
{
    private const FROM_LITERAL = 'Fp4\PHP\Module\ArrayList\fromLiteral';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event->getExpr(),
            proveOf(FuncCall::class),
            O\filter(fn(FuncCall $c) => self::FROM_LITERAL === $c->name->getAttribute('resolvedName')),
            O\filter(fn(FuncCall $c) => !$c->isFirstClassCallable()),
            O\flatMap(PsalmApi::$types->getExprType($event)),
            O\filter(fn(Union $type) => !$type->containsAnyLiteral()),
            O\tap(fn(Union $type) => self::raiseIssueIfTypeIsNotLiteral($type, $event)),
            constNull(...),
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
