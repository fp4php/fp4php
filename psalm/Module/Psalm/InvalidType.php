<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Psalm;

use Fp4\PHP\Option as O;
use Fp4\PHP\Option\Option;
use Psalm\CodeLocation;
use Psalm\Issue\CodeIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

final class InvalidType extends CodeIssue
{
    /**
     * @return Option<never>
     */
    public static function raise(string $type, AfterExpressionAnalysisEvent $event): Option
    {
        $source = $event->getStatementsSource();

        IssueBuffer::maybeAdd(
            e: new self(
                message: "Fail to parse '{$type}' type.",
                code_location: new CodeLocation($source, $event->getExpr()),
            ),
            suppressed_issues: $source->getSuppressedIssues(),
        );

        return O\none;
    }
}
