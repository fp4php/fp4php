<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Psalm;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function count;
use function Fp4\PHP\Module\Functions\pipe;

final class SuppressIssueHandler implements FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Module\Psalm\suppressIssue'),
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getStatementsSource();
        $location = new CodeLocation($source, $event->getStmt());

        $removedIssues = pipe(
            O\bindable(),
            O\bind(
                issue: fn() => pipe(
                    $event->getCallArgs(),
                    L\second(...),
                    O\map(fn(Arg $arg) => $arg->value),
                    O\flatMap(PsalmApi::$types->getExprType($source)),
                    O\flatMap(PsalmApi::$types->asSingleAtomicOf(TLiteralString::class)),
                    O\map(fn(TLiteralString $literal) => $literal->value),
                ),
                message: fn() => pipe(
                    $event->getCallArgs(),
                    L\third(...),
                    O\map(fn(Arg $arg) => $arg->value),
                    O\flatMap(PsalmApi::$types->getExprType($source)),
                    O\flatMap(PsalmApi::$types->asSingleAtomicOf(TLiteralString::class)),
                    O\map(fn(TLiteralString $literal) => $literal->value),
                ),
            ),
            O\map(fn($i) => pipe(
                IssueBuffer::getIssuesData()[$source->getFilePath()] ?? [],
                L\filter(self::isWithin($location)),
                L\filter(self::isSameIssue($i->issue)),
                L\filter(self::isSameMessage($i->message)),
            )),
            O\getOrElse([]),
            L\tap(self::removeIssue(...)),
        );

        if (0 === count($removedIssues)) {
            $e = new InvalidArgument(
                message: 'Unused psalm suppress',
                code_location: $location,
            );

            IssueBuffer::maybeAdd($e, $source->getSuppressedIssues());
        }

        return null;
    }

    public static function removeIssue(IssueData $data): void
    {
        IssueBuffer::remove($data->file_path, $data->type, $data->from);
    }

    /**
     * @return Closure(IssueData): bool
     */
    public static function isWithin(CodeLocation $location): Closure
    {
        return fn(IssueData $data) => $location->getLineNumber() <= $data->line_from && $data->line_to <= $location->getEndLineNumber();
    }

    /**
     * @return Closure(IssueData): bool
     */
    public static function isSameIssue(string $issue): Closure
    {
        return fn(IssueData $data) => $data->type === $issue;
    }

    /**
     * @return Closure(IssueData): bool
     */
    public static function isSameMessage(string $message): Closure
    {
        return fn(IssueData $data) => $data->message === $message;
    }
}
