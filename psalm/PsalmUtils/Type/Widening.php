<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Type;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Module\Combinator\constVoid;
use function Fp4\PHP\Module\Combinator\pipe;

final class Widening
{
    /**
     * @param non-empty-list<non-empty-string> $names
     * @return Option<void>
     */
    public static function widen(AfterExpressionAnalysisEvent $event, array $names, AsNonLiteralTypeConfig $config = new AsNonLiteralTypeConfig()): Option
    {
        return pipe(
            O\some($event->getExpr()),
            O\filterOf([FuncCall::class, ConstFetch::class]),
            O\filter(fn(FuncCall|ConstFetch $c) => pipe(
                $names,
                L\contains($c->name->getAttribute('resolvedName')),
            )),
            O\filter(fn(FuncCall|ConstFetch $c) => $c instanceof ConstFetch || !$c->isFirstClassCallable()),
            O\flatMap(PsalmApi::$type->get($event)),
            O\map(fn($t) => PsalmApi::$cast->toNonLiteralType($t, $config)),
            O\tap(PsalmApi::$type->set($event->getExpr(), $event)),
            O\map(constVoid(...)),
        );
    }
}
