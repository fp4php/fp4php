<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Bindable;

use Closure;
use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;
use Psalm\Issue\CodeIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;

use function Fp4\PHP\Module\Functions\pipe;

final class PropertyIsNotDefinedInScope extends CodeIssue
{
    /**
     * @return Option<never>
     */
    public static function raise(array $context, string $property, MethodReturnTypeProviderEvent $event): Option
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
     * @return Closure(list<string|int>): Option<string>
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
