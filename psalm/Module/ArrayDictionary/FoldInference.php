<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayDictionary;

use Fp4\PHP\PsalmIntegration\PsalmUtils\Fold\FoldHandler;
use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\BeforeExpressionAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\BeforeExpressionAnalysisEvent;

final class FoldInference implements BeforeExpressionAnalysisInterface, AfterExpressionAnalysisInterface
{
    public static function beforeExpressionAnalysis(BeforeExpressionAnalysisEvent $event): ?bool
    {
        return FoldHandler::beforeExpressionAnalysis($event, [
            'Fp4\PHP\Module\ArrayDictionary\fold',
            'Fp4\PHP\Module\ArrayDictionary\foldKV',
        ]);
    }

    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
    {
        return FoldHandler::afterExpressionAnalysis($event);
    }
}
