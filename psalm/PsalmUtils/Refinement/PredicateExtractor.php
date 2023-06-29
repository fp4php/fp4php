<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\FunctionLike;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Module\Functions\pipe;

final class PredicateExtractor
{
    /**
     * @return Closure(AfterExpressionAnalysisEvent): Option<FunctionLike>
     */
    public static function extract(string $function): Closure
    {
        return fn(AfterExpressionAnalysisEvent $event) => pipe(
            O\some($event->getExpr()),
            O\filterOf(Expr\FuncCall::class),
            O\filter(fn(Expr\FuncCall $call) => $call->name->getAttribute('resolvedName') === $function),
            O\filter(fn(Expr\FuncCall $call) => !$call->isFirstClassCallable()),
            O\flatMap(fn(Expr\FuncCall $call) => pipe(
                L\fromIterable($call->getArgs()),
                L\first(...),
            )),
            O\map(fn(Arg $arg) => $arg->value),
            O\filterOf([Expr\Closure::class, Expr\ArrowFunction::class]),
            O\orElse(fn() => FirstClassCallablePredicate::mock($event)),
        );
    }
}
