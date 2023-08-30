<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Either;

use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindableBuilder as BaseBindableBuilder;
use Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable\BindLetType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Bindable;
use Fp4\PHP\Type\Either;
use Psalm\Plugin\DynamicTemplateProvider;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Combinator\pipe;

final class BindableBuilder implements BaseBindableBuilder
{
    /** @var array<string, TTemplateParam> */
    private array $previousProperties = [];

    /** @var list<TTemplateParam> */
    private array $previousLefts = [];

    private int $functionOffset = 0;

    private readonly TTemplateParam $leftTemplate;
    private readonly TTemplateParam $boundTemplate;

    public function __construct(
        private readonly DynamicTemplateProvider $templates,
        private readonly BindLetType $type,
    ) {
        $this->leftTemplate = $templates->createTemplate('E', Type::getMixed());
        $this->boundTemplate = $templates->createTemplate('TBound', Type::getObject());
    }

    public function getNextFunction(string $forProperty): FunctionLikeParameter
    {
        return pipe(
            PsalmApi::$create->callable(
                params: pipe($this->compiledBindable(), PsalmApi::$create->param('context')),
                return: $this->getNextReturnType($forProperty),
            ),
            PsalmApi::$create->param($forProperty),
        );
    }

    public function getTemplates(): array
    {
        return pipe(
            L\fromIterable([...$this->previousProperties, ...$this->previousLefts]),
            L\prepend($this->leftTemplate),
            L\prepend($this->boundTemplate),
        );
    }

    public function getReturnType(): Union
    {
        return PsalmApi::$create->closure(
            params: pipe(
                [
                    $this->leftTemplate,
                    pipe($this->boundTemplate, PsalmApi::$create->genericObject(Bindable::class)),
                ],
                PsalmApi::$create->genericObject(Either::class),
                PsalmApi::$create->param('context'),
            ),
            return: pipe(
                [$this->compileLefts(), $this->compiledBindable()],
                PsalmApi::$create->genericObject(Either::class),
            ),
        );
    }

    private function getNextReturnType(string $propertyName): Union
    {
        $functionOffset = $this->functionOffset++;

        $property = $this->templates->createTemplate("T{$functionOffset}");
        $this->previousProperties[$propertyName] = $property;

        if (BindLetType::LET === $this->type) {
            return pipe($property, PsalmApi::$create->union(...));
        }

        $left = $this->templates->createTemplate("E{$functionOffset}");
        $this->previousLefts[] = $left;

        return pipe([$left, $property], PsalmApi::$create->genericObject(Either::class));
    }

    private function compileLefts(): Union
    {
        return PsalmApi::$create->union([$this->leftTemplate, ...$this->previousLefts]);
    }

    private function compiledBindable(): Union
    {
        return pipe(
            $this->boundTemplate,
            PsalmApi::$create->genericObject(Bindable::class, withIntersections: pipe(
                Ev\proveNonEmptyArray($this->previousProperties),
                O\map(PsalmApi::$create->objectWithProperties(...)),
                O\map(PsalmApi::$create->genericObjectAtomic(Bindable::class)),
                O\getOrElse([]),
            )),
        );
    }
}
