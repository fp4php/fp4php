<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\ArrayList;

use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindableBuilder as BaseBindableBuilder;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindLetType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Bindable;
use Psalm\Plugin\DynamicTemplateProvider;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;

final class BindableBuilder implements BaseBindableBuilder
{
    private readonly TTemplateParam $boundTemplate;

    /** @var array<string, TTemplateParam> */
    private array $previousProperties = [];

    private int $functionOffset = 0;

    public function __construct(
        private readonly DynamicTemplateProvider $templates,
        private readonly BindLetType $type,
    ) {
        $this->boundTemplate = $templates->createTemplate('TBound', Type::getObject());
    }

    public function getNextFunction(string $forProperty): FunctionLikeParameter
    {
        return new FunctionLikeParameter(
            name: $forProperty,
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
                    return_type: $this->getNextReturnTypeFor($forProperty),
                ),
            ]),
            is_optional: false,
        );
    }

    public function getTemplates(): array
    {
        return pipe(
            L\fromIterable($this->previousProperties),
            L\prepend($this->boundTemplate),
        );
    }

    public function getReturnType(): Union
    {
        $inputBindable = new Union([
            new TGenericObject(Bindable::class, [
                new Union([$this->boundTemplate]),
            ]),
        ]);

        $outputBindable = $this->compiledBindable();

        return new Union([
            new TClosure(
                value: 'Closure',
                params: [
                    new FunctionLikeParameter(
                        name: 'context',
                        by_ref: false,
                        type: new Union([
                            Type::getListAtomic($inputBindable),
                        ]),
                        is_optional: false,
                    ),
                ],
                return_type: new Union([
                    Type::getListAtomic($outputBindable),
                ]),
            ),
        ]);
    }

    private function getNextReturnTypeFor(string $propertyName): Union
    {
        $property = $this->templates->createTemplate('T'.($this->functionOffset++));
        $this->previousProperties[$propertyName] = $property;

        $returnType = new Union([$property]);

        return match ($this->type) {
            BindLetType::LET => $returnType,
            BindLetType::BIND => new Union([
                Type::getListAtomic($returnType),
            ]),
        };
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
     * @param non-empty-array<TTemplateParam> $properties
     */
    private static function toObjectWithProperties(array $properties): TGenericObject
    {
        $props = new TObjectWithProperties(pipe(
            D\from($properties),
            D\map(PsalmApi::$types->asUnion(...)),
        ));

        return new TGenericObject(Bindable::class, [
            new Union([$props]),
        ]);
    }
}
