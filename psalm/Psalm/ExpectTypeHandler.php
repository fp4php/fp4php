<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Psalm;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Internal\Type\TypeParser;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Issue\CheckType;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class ExpectTypeHandler implements FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Module\Psalm\assertType'),
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        $source = $event->getStatementsSource();

        return pipe(
            O\bindable(),
            O\bind(
                expectedType: fn() => pipe(
                    $event->getCallArgs(),
                    L\first(...),
                    O\map(fn(Arg $arg) => $arg->value),
                    O\flatMap(PsalmApi::$types->getExprType($source)),
                    O\flatMap(PsalmApi::$types->asSingleAtomicOf(TLiteralString::class)),
                    O\flatMap(fn(TLiteralString $type) => O\tryCatch(
                        fn() => TypeParser::parseTokens(
                            TypeTokenizer::getFullyQualifiedTokens(
                                string_type: $type->value,
                                aliases: $source->getAliases(),
                            ),
                        ),
                    )),
                ),
                exprType: fn() => pipe(
                    $event->getCallArgs(),
                    L\second(...),
                    O\map(fn(Arg $arg) => $arg->value),
                    O\flatMap(PsalmApi::$types->getExprType($source)),
                ),
            ),
            O\filter(fn($i) => $i->exprType->getId() !== $i->expectedType->getId()),
            O\map(fn($i) => new CheckType(
                message: "Type {$i->exprType} does not match to type {$i->expectedType}",
                code_location: new CodeLocation($source, $event->getStmt()),
            )),
            O\tap(IssueBuffer::maybeAdd(...)),
            constNull(...),
        );
    }
}
