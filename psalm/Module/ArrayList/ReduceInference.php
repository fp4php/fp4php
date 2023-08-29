<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Reduce\ReduceHandler;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\BeforeExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\BeforeExpressionAnalysisEvent;

final class ReduceInference implements BeforeExpressionAnalysisInterface, AfterExpressionAnalysisInterface
{
    public static function beforeExpressionAnalysis(BeforeExpressionAnalysisEvent $event): ?bool
    {
        return ReduceHandler::beforeExpressionAnalysis($event, [
            'Fp4\PHP\Module\ArrayList\reduce',
            'Fp4\PHP\Module\ArrayList\reduceKV',
        ]);
    }

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return ReduceHandler::afterExpressionAnalysis($event);
    }
}
