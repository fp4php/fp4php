<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Option;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Expr\FuncCall;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Module\Evidence\proveOf;
use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class SomeCallWidening implements AfterExpressionAnalysisInterface
{
    private const SOME = 'Fp4\PHP\Module\Option\some';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event->getExpr(),
            proveOf(FuncCall::class),
            O\filter(fn (FuncCall $c) => self::SOME === $c->name->getAttribute('resolvedName')),
            O\filter(fn (FuncCall $c) => !$c->isFirstClassCallable()),
            O\flatMap(PsalmApi::$types->getExprType($event)),
            O\map(PsalmApi::$types->asNonLiteralType(...)),
            O\tap(PsalmApi::$types->setType($event->getExpr(), $event)),
            constNull(...),
        );
    }
}
