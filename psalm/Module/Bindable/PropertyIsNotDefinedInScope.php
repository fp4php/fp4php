<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Bindable;

use Closure;
use Fp4\PHP\ArrayDictionary as D;
use Fp4\PHP\Option as O;
use Psalm\Issue\CodeIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;

use function Fp4\PHP\Combinator\pipe;

final class PropertyIsNotDefinedInScope extends CodeIssue
{
    /**
     * @return O\Option<never>
     */
    public static function raise(array $context, string $property, MethodReturnTypeProviderEvent $event): O\Option
    {
        $source = $event->getSource();
        $error = "Property '{$property}' is not defined in the bindable scope.";

        IssueBuffer::maybeAdd(
            e: new self(
                message: pipe(
                    D\keys($context),
                    self::findSimilarKeys($property),
                    O\map(fn(string $similar) => "{$error} Did you mean {$similar}?"),
                    O\getOrCall(fn() => $error),
                ),
                code_location: $event->getCodeLocation(),
            ),
            suppressed_issues: $source->getSuppressedIssues(),
        );

        return O\none;
    }

    /**
     * @return Closure(list<string|int>): O\Option<string>
     */
    private static function findSimilarKeys(string $actual): Closure
    {
        return function(array $keys) use ($actual) {
            $similar = [];

            foreach ($keys as $key) {
                similar_text((string) $key, $actual, $percent);

                if ($percent > 70.00) {
                    $similar[] = "'{$key}'";
                }
            }

            return [] !== $similar
                ? O\some(implode(', ', $similar))
                : O\none;
        };
    }
}
