<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Psalm;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Issue\Trace;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class DumpTypeHandler implements FunctionReturnTypeProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Module\Psalm\dumpType'),
        ];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Union
    {
        return pipe(
            $event->getCallArgs(),
            L\first(...),
            O\map(fn(Arg $arg) => $arg->value),
            O\flatMap(PsalmApi::$types->getExprType($event)),
            O\map(fn(Union $type) => new Trace(
                message: $type->getId(),
                code_location: new CodeLocation($event->getStatementsSource(), $event->getStmt()),
            )),
            O\tap(IssueBuffer::maybeAdd(...)),
            constNull(...),
        );
    }
}
