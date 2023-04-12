<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Bindable;

use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\EventHandler\DynamicFunctionStorageProviderInterface;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Union;

final class BindFunctionStorageProvider implements DynamicFunctionStorageProviderInterface
{
    public static function getFunctionIds(): array
    {
        return [
            strtolower('Fp4\PHP\Module\Option\bind'),
        ];
    }

    public static function getFunctionStorage(DynamicFunctionStorageProviderEvent $event): ?DynamicFunctionStorage
    {
        $templates = $event->getTemplateProvider();

        $firstTemplate = $templates->createTemplate('T0');
        $secondTemplate = $templates->createTemplate('T1');
        $thirdTemplate = $templates->createTemplate('T2');

        $boundTemplate = $templates->createTemplate('TBound', new Union([
            new TNamedObject(Bindable::class),
        ]));

        $firstClosure = new TClosure(
            value: 'Closure',
            params: [
                new FunctionLikeParameter(
                    name: 'context',
                    by_ref: false,
                    type: new Union([$boundTemplate]),
                ),
            ],
            return_type: new Union([
                new TGenericObject(Option::class, [
                    new Union([$firstTemplate]),
                ]),
            ]),
        );

        $secondClosure = new TClosure(
            value: 'Closure',
            params: [
                new FunctionLikeParameter(
                    name: 'context',
                    by_ref: false,
                    type: new Union([
                        $boundTemplate->addIntersectionType(
                            new TObjectWithProperties([
                                'a' => new Union([$firstTemplate]),
                            ]),
                        ),
                    ]),
                ),
            ],
            return_type: new Union([
                new TGenericObject(Option::class, [
                    new Union([$secondTemplate]),
                ]),
            ]),
        );

        $thirdClosure = new TClosure(
            value: 'Closure',
            params: [
                new FunctionLikeParameter(
                    name: 'context',
                    by_ref: false,
                    type: new Union([
                        $boundTemplate->addIntersectionType(
                            new TObjectWithProperties([
                                'a' => new Union([$firstTemplate]),
                                'b' => new Union([$secondTemplate]),
                            ]),
                        ),
                    ]),
                ),
            ],
            return_type: new Union([
                new TGenericObject(Option::class, [
                    new Union([$thirdTemplate]),
                ]),
            ]),
        );

        $returnClosure = new TClosure(
            value: 'Closure',
            params: [
                new FunctionLikeParameter(
                    name: 'context',
                    by_ref: false,
                    type: new Union([
                        new TGenericObject(Option::class, [
                            new Union([$boundTemplate]),
                        ]),
                    ]),
                ),
            ],
            return_type: new Union([
                new TGenericObject(Option::class, [
                    new Union([
                        $boundTemplate->addIntersectionType(
                            new TObjectWithProperties([
                                'a' => new Union([$firstTemplate]),
                                'b' => new Union([$secondTemplate]),
                                'c' => new Union([$thirdTemplate]),
                            ]),
                        ),
                    ]),
                ]),
            ]),
        );

        $storage = new DynamicFunctionStorage();
        $storage->params = [
            new FunctionLikeParameter(name: 'a', by_ref: false, type: new Union([$firstClosure])),
            new FunctionLikeParameter(name: 'b', by_ref: false, type: new Union([$secondClosure])),
            new FunctionLikeParameter(name: 'c', by_ref: false, type: new Union([$thirdClosure])),
        ];
        $storage->return_type = new Union([$returnClosure]);
        $storage->templates = [
            $firstTemplate,
            $secondTemplate,
            $thirdTemplate,
            $boundTemplate,
        ];

        return $storage;
    }
}
