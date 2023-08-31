<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Combinator;

use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Evidence as Ev;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\constNull;
use function Fp4\PHP\Combinator\pipe;

final class PipeErrorLocator implements AfterExpressionAnalysisInterface
{
    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            O\some($event->getExpr()),
            O\filterOf(FuncCall::class),
            O\filter(fn(FuncCall $call) => 'Fp4\PHP\Combinator\pipe' === $call->name->getAttribute('resolvedName')),
            O\filter(fn(FuncCall $call) => !$call->isFirstClassCallable()),
            O\flatMap(fn(FuncCall $call) => pipe(
                $call->getArgs(),
                Ev\proveNonEmptyList(...),
            )),
            O\flatMap(fn(array $args) => pipe(
                O\bindable(),
                O\bind(
                    head: fn() => self::getInitArg($args, $event),
                    tail: fn() => self::getFunctionArgs($args, $event),
                ),
            )),
            O\tap(fn($i) => self::check($i->head, $i->tail, $event)),
            constNull(...),
        );
    }

    /**
     * @param non-empty-list<PipeUnaryFunctionArg> $next
     */
    private static function check(Union $previous, array $next, AfterExpressionAnalysisEvent $event): void
    {
        if (!PsalmApi::$codebase->isTypeContainedByType($previous, $next[0]->input)) {
            PipeTypeMismatch::raise($previous, $next[0], $event);
        }

        $rest = L\tail($next);

        if (!empty($rest)) {
            self::check($next[0]->output, $rest, $event);
        }
    }

    /**
     * @param non-empty-list<Arg> $args
     * @return O\Option<Union>
     */
    private static function getInitArg(array $args, AfterExpressionAnalysisEvent $event): O\Option
    {
        return pipe(
            $args,
            L\first(...),
            O\map(fn(Arg $arg) => $arg->value),
            O\flatMap(PsalmApi::$type->get($event)),
        );
    }

    /**
     * @param non-empty-list<Arg> $args
     * @return O\Option<non-empty-list<PipeUnaryFunctionArg>>
     */
    private static function getFunctionArgs(array $args, AfterExpressionAnalysisEvent $event): O\Option
    {
        return pipe(
            L\tail($args),
            Ev\proveNonEmptyList(...),
            O\flatMap(
                L\traverseOption(fn(Arg $arg) => pipe(
                    O\some($arg->value),
                    O\flatMap(PsalmApi::$type->get($event)),
                    O\flatMap(PsalmApi::$cast->toSingleAtomicOf([TClosure::class, TCallable::class])),
                    O\flatMap(fn(TClosure|TCallable $function) => PipeUnaryFunctionArg::from($arg, $function)),
                )),
            ),
        );
    }
}
