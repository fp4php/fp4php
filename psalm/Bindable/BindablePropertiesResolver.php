<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Bindable;

use Fp4\PHP\Type\Bindable;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type\Union;

final class BindablePropertiesResolver implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return [
            Bindable::class,
        ];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        return null;
    }
}
