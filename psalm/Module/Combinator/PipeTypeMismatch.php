<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Combinator;

use Fp4\PHP\Option as O;
use Psalm\CodeLocation;
use Psalm\Issue\CodeIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Union;

final class PipeTypeMismatch extends CodeIssue
{
    /**
     * @return O\Option<never>
     */
    public static function raise(Union $previous, PipeUnaryFunctionArg $unaryFunctionArg, AfterExpressionAnalysisEvent $event): O\Option
    {
        $source = $event->getStatementsSource();

        IssueBuffer::maybeAdd(
            e: new self(
                message: "Type {$previous->getId()} should be a subtype of {$unaryFunctionArg->input->getId()} for function {$unaryFunctionArg->original->getId()}.",
                code_location: new CodeLocation($source, $unaryFunctionArg->node),
            ),
            suppressed_issues: $source->getSuppressedIssues(),
        );

        return O\none;
    }
}
