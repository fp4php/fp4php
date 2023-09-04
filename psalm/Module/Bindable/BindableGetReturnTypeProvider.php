<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Bindable;

use Closure;
use Fp4\PHP\ArrayDictionary as D;
use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Bindable;
use Fp4\PHP\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindableFoldType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\MethodReturnTypeProviderInterface;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\pipe;

final class BindableGetReturnTypeProvider implements MethodReturnTypeProviderInterface
{
    public static function getClassLikeNames(): array
    {
        return [
            Bindable::class,
        ];
    }

    public static function getMethodReturnType(MethodReturnTypeProviderEvent $event): ?Union
    {
        $context = $event->getContext();

        return pipe(
            O\some($event->getStmt()),
            O\filter(fn() => Bindable::class !== $context->self),
            O\filter(fn() => '__get' === $event->getMethodNameLowercase()),
            O\filterOf(MethodCall::class),
            O\flatMap(self::getReturnType($event)),
            O\getOrNull(...),
        );
    }

    /**
     * @return Closure(MethodCall): O\Option<Union>
     */
    private static function getReturnType(MethodReturnTypeProviderEvent $event): Closure
    {
        return fn(MethodCall $call) => pipe(
            L\fromIterable($call->getArgs()),
            L\first(...),
            O\map(fn(Arg $arg) => $arg->value),
            O\filterOf(VirtualString::class),
            O\flatMap(fn(VirtualString $string) => pipe(
                O\some($call->var),
                O\flatMap(PsalmApi::$type->get($event)),
                O\flatMap(BindableFoldType::for(...)),
                O\flatMap(self::getPropertyFromBindableScope($string->value, $event)),
            )),
        );
    }

    /**
     * @return Closure(Union[]): O\Option<Union>
     */
    private static function getPropertyFromBindableScope(string $property, MethodReturnTypeProviderEvent $event): Closure
    {
        return fn(array $context) => O\first(
            fn() => pipe($context, D\get($property)),
            fn() => PropertyIsNotDefinedInScope::raise($context, $property, $event),
        );
    }
}
