<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\ArrayList;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Expr\FuncCall;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

use function Fp4\PHP\Module\Evidence\proveOf;
use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class FromIterableCallWidening implements AfterExpressionAnalysisInterface
{
    private const FROM = 'Fp4\PHP\Module\ArrayList\from';
    private const FROM_ITERABLE = 'Fp4\PHP\Module\ArrayList\fromIterable';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            $event->getExpr(),
            proveOf(FuncCall::class),
            O\filter(fn (FuncCall $c) => pipe(
                L\fromIterable([self::FROM, self::FROM_ITERABLE]),
                L\contains($c->name->getAttribute('resolvedName')),
            )),
            O\filter(fn (FuncCall $c) => !$c->isFirstClassCallable()),
            O\flatMap(PsalmApi::$types->getExprType($event)),
            O\map(PsalmApi::$types->asNonLiteralType(...)),
            O\tap(PsalmApi::$types->setExprType($event->getExpr(), $event)),
            constNull(...),
        );
    }
}
