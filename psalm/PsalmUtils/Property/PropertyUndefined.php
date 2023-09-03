<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Property;

use Closure;
use Fp4\PHP\Option as O;
use Psalm\CodeLocation;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Union;

final class PropertyUndefined extends CodeIssue
{
    /**
     * @return Closure(AfterExpressionAnalysisEvent $event): O\Option<never>
     */
    public static function raise(string $property, Union $object): Closure
    {
        return function(AfterExpressionAnalysisEvent $event) use ($property, $object) {
            $expr = $event->getExpr();
            $source = $event->getStatementsSource();

            $e = new InvalidArgument(
                message: "Property '{$property}' on {$object->getId()} is not defined.",
                code_location: new CodeLocation($source, $expr),
            );

            IssueBuffer::maybeAdd($e, $source->getSuppressedIssues());

            return O\none;
        };
    }
}
