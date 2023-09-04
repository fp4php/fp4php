<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Combinator;

use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Evidence as Ev;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Issue\InvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class TupledReturnTypeProvider implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            O\some($event->getExpr()),
            O\filterOf(FuncCall::class),
            O\filter(fn($funcCall) => 'Fp4\PHP\Combinator\tupled' === $funcCall->name->getAttribute('resolvedName')),
            O\filter(fn($funcCall) => !$funcCall->isFirstClassCallable()),
            O\flatMap(fn($funcCall) => pipe(
                O\bindable(),
                O\bind(
                    expectedClosure: fn() => pipe(
                        L\fromIterable($funcCall->getArgs()),
                        L\first(...),
                        O\map(fn(Arg $arg) => $arg->value),
                        O\filter(fn($tupled) => !$tupled->hasAttribute('signatureDynamicallyProvided')),
                        O\flatMap(PsalmApi::$type->get($event)),
                        O\flatMap(PsalmApi::$cast->toSingleAtomicOf([TCallable::class, TClosure::class])),
                    ),
                    actualClosure: fn() => pipe(
                        O\some($funcCall),
                        O\flatMap(PsalmApi::$type->get($event)),
                        O\flatMap(PsalmApi::$cast->toSingleAtomicOf([TCallable::class, TClosure::class])),
                    ),
                    expectedTupleType: fn($i) => pipe(
                        $i->expectedClosure->params ?? [],
                        L\map(fn($param) => $param->type ?? PsalmApi::$create->mixed()),
                        Ev\proveNonEmptyList(...),
                        O\map(PsalmApi::$create->keyedArrayList(...)),
                    ),
                    actualTupleType: fn($i) => pipe(
                        $i->actualClosure->params ?? [],
                        L\first(...),
                        O\flatMapNullable(fn($param) => $param->type),
                    ),
                ),
                O\tap(function($i) use ($event): void {
                    $source = $event->getStatementsSource();
                    $expr = $event->getExpr();

                    if (PsalmApi::$codebase->isTypeContainedByType($i->actualTupleType, $i->expectedTupleType)) {
                        return;
                    }

                    $e = new InvalidArgument(
                        message: "Type {$i->actualClosure->getId()} is not assignable to {$i->expectedClosure->getId()}",
                        code_location: new CodeLocation($source, $expr),
                    );

                    IssueBuffer::maybeAdd($e, $source->getSuppressedIssues());
                }),
            )),
            constNull(...),
        );
    }
}
