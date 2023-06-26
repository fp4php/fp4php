<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Psalm;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Issue\CheckType;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TLiteralString;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class TypeCheckerHandler implements AfterExpressionAnalysisInterface
{
    public const IS_ASSIGNABLE_TO = 'Fp4\PHP\Module\Psalm\isAssignableTo';
    public const IS_SAME_AS = 'Fp4\PHP\Module\Psalm\isSameAs';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        $source = $event->getStatementsSource();

        return pipe(
            O\bindable(),
            O\bind(
                expresion: fn() => pipe(
                    O\some($event->getExpr()),
                    O\flatMap(Ev\proveOf(FuncCall::class)),
                    O\filter(fn(FuncCall $c) => !$c->isFirstClassCallable()),
                ),
                function: fn($i) => pipe(
                    Ev\proveNonEmptyString($i->expresion->name->getAttribute('resolvedName')),
                    O\filter(fn(string $name) => self::IS_ASSIGNABLE_TO === $name || self::IS_SAME_AS === $name),
                ),
                inferredType: fn($i) => pipe(
                    O\some($i->expresion),
                    O\flatMap(PsalmApi::$types->getExprType($event)),
                    O\flatMap(PsalmApi::$types->asSingleAtomicOf(TClosure::class)),
                    O\map(fn(TClosure $closure) => $closure->return_type ?? Type::getMixed()),
                ),
                expectedType: fn($i) => pipe(
                    O\some($i->expresion),
                    O\flatMap(fn(FuncCall $c) => pipe(
                        L\fromIterable($c->getArgs()),
                        L\first(...),
                    )),
                    O\map(fn(Arg $arg) => $arg->value),
                    O\flatMap(PsalmApi::$types->getExprType($event)),
                    O\flatMap(PsalmApi::$types->asSingleAtomicOf(TLiteralString::class)),
                    O\flatMap(self::parseType($source)),
                ),
            ),
            O\filter(fn($i) => match ($i->function) {
                self::IS_SAME_AS => $i->inferredType->getId() !== $i->expectedType->getId(),
                default => !PsalmApi::$codebase->isTypeContainedByType($i->inferredType, $i->expectedType),
            }),
            O\map(fn($i) => new CheckType(
                message: match ($i->function) {
                    self::IS_SAME_AS => "The type {$i->inferredType} is not exactly the same as the type {$i->expectedType}",
                    default => "The type {$i->inferredType} is not assignable to the type {$i->expectedType}",
                },
                code_location: new CodeLocation($source, $event->getExpr()),
            )),
            O\tap(fn(CheckType $issue) => IssueBuffer::maybeAdd(
                e: $issue,
                suppressed_issues: $source->getSuppressedIssues(),
            )),
            constNull(...),
        );
    }

    /**
     * @return Closure(TLiteralString): Option<Type\Union>
     */
    private static function parseType(StatementsSource $source): Closure
    {
        return fn(TLiteralString $literal) => O\tryCatch(fn() => TypeParser::parseTokens(
            TypeTokenizer::getFullyQualifiedTokens(
                string_type: $literal->value,
                aliases: $source->getAliases(),
            ),
        ));
    }
}
