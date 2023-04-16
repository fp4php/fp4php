<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\FunctionLike;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Module\Functions\pipe;

final class PredicateExtractor
{
    /**
     * @return Option<FunctionLike>
     */
    public static function extract(AfterExpressionAnalysisEvent $event, string $function): Option
    {
        return pipe(
            $event->getExpr(),
            Ev\proveOf(FuncCall::class),
            O\filter(fn(FuncCall $call) => $call->name->getAttribute('resolvedName') === $function),
            O\filter(fn(FuncCall $call) => !$call->isFirstClassCallable()),
            O\map(fn(FuncCall $call) => pipe(
                $call->getArgs(),
                L\fromIterable(...),
            )),
            O\flatMap(L\first(...)),
            O\map(fn(Arg $arg) => $arg->value),
            O\flatMap(Ev\proveOf([Closure::class, ArrowFunction::class])),
            // O\orElse(fn() => FirstClassCallablePredicate::mock($event)),
        );
    }
}
