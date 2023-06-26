<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Option;

use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Type\Widening;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\constNull;
use function Fp4\PHP\Module\Functions\pipe;

final class NoneConstInference implements AfterExpressionAnalysisInterface
{
    private const NONE = 'Fp4\PHP\Module\Option\none';

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return pipe(
            Widening::widen($event, [self::NONE]),
            O\map(function() {
                $option = new TGenericObject(Option::class, [
                    Type::getNever(),
                ]);

                return new Union([$option]);
            }),
            O\tap(PsalmApi::$types->setExprType($event->getExpr(), $event)),
            constNull(...),
        );
    }
}
