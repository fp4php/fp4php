<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Bindable;

use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Arg;
use PhpParser\Node\Identifier;
use Psalm\Plugin\DynamicFunctionStorage;
use Psalm\Plugin\DynamicTemplateProvider;
use Psalm\Plugin\EventHandler\Event\DynamicFunctionStorageProviderEvent;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;

final class BindableFunctionBuilder
{
    private readonly TTemplateParam $boundTemplate;

    /** @var array<string, TTemplateParam> */
    private array $previousProperties = [];

    private int $functionOffset = 0;

    private function __construct(
        private readonly BindLetType $type,
        private readonly DynamicTemplateProvider $templates,
    ) {
        $this->boundTemplate = $templates->createTemplate('TBound', Type::getObject());
    }

    /**
     * @return Option<DynamicFunctionStorage>
     */
    public static function buildStorage(DynamicFunctionStorageProviderEvent $event, BindLetType $type): Option
    {
        $args = $event->getArgs();

        if (empty($args)) {
            return O\none;
        }

        $self = new self($type, $event->getTemplateProvider());

        return pipe(
            $args,
            L\traverseOption(fn(Arg $arg) => pipe(
                $arg->name,
                Ev\proveOf(Identifier::class),
                O\map($self->nextFunction(...)),
            )),
            O\map(function(array $params) use ($self) {
                $storage = new DynamicFunctionStorage();
                $storage->params = $params;
                $storage->templates = pipe(
                    L\fromIterable($self->previousProperties),
                    L\prepend($self->boundTemplate),
                );
                $storage->return_type = $self->returnType();

                return $storage;
            }),
        );
    }

    private function nextFunction(Identifier $property): FunctionLikeParameter
    {
        return new FunctionLikeParameter(
            name: $property->toString(),
            by_ref: false,
            type: new Union([
                new TCallable(
                    value: 'callable',
                    params: [
                        new FunctionLikeParameter(
                            name: 'context',
                            by_ref: false,
                            type: $this->compiledBindable(),
                            is_optional: false,
                        ),
                    ],
                    return_type: match ($this->type) {
                        BindLetType::BIND => new Union([
                            new TGenericObject(Option::class, [
                                $this->nextReturnTypeFor($property->toString()),
                            ]),
                        ]),
                        BindLetType::LET => $this->nextReturnTypeFor($property->toString()),
                    },
                ),
            ]),
            is_optional: false,
        );
    }

    private function compiledBindable(): Union
    {
        $bindable = new TGenericObject(Bindable::class, [
            new Union([$this->boundTemplate]),
        ]);

        return new Union([
            [] !== $this->previousProperties
                ? $bindable->addIntersectionType(self::toObjectWithProperties($this->previousProperties))
                : $bindable,
        ]);
    }

    /**
     * @param non-empty-array<string, TTemplateParam> $properties
     */
    private static function toObjectWithProperties(array $properties): TGenericObject
    {
        $props = new TObjectWithProperties(pipe(
            D\from($properties),
            D\map(fn(TTemplateParam $type) => PsalmApi::$types->asUnion($type)),
        ));

        return new TGenericObject(Bindable::class, [
            new Union([$props]),
        ]);
    }

    private function nextReturnTypeFor(string $propertyName): Union
    {
        $property = $this->templates->createTemplate('T'.($this->functionOffset++));
        $this->previousProperties[$propertyName] = $property;

        return new Union([$property]);
    }

    private function returnType(): Union
    {
        $flowBindable = new Union([
            new TGenericObject(Bindable::class, [
                new Union([$this->boundTemplate]),
            ]),
        ]);

        return new Union([
            new TClosure(
                value: 'Closure',
                params: [
                    new FunctionLikeParameter(
                        name: 'context',
                        by_ref: false,
                        type: new Union([
                            new TGenericObject(Option::class, [$flowBindable]),
                        ]),
                        is_optional: false,
                    ),
                ],
                return_type: new Union([
                    new TGenericObject(Option::class, [
                        $this->compiledBindable(),
                    ]),
                ]),
            ),
        ]);
    }
}
