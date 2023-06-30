<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable;

use Closure;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Bindable;
use Psalm\Plugin\DynamicTemplateProvider;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function Fp4\PHP\Module\Functions\pipe;

final class SingleTypeParameterBindableBuilder implements BindableBuilder
{
    /** @var array<string, TTemplateParam> */
    private array $previousProperties = [];
    private int $functionOffset = 0;

    private readonly TTemplateParam $boundTemplate;

    /**
     * @param Closure(Union): Union $liftF
     */
    public function __construct(
        private readonly DynamicTemplateProvider $templates,
        private readonly BindLetType $type,
        private readonly Closure $liftF,
    ) {
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
            L\fromIterable($this->previousProperties),
            L\prepend($this->boundTemplate),
        );
    }

    public function getReturnType(): Union
    {
        return PsalmApi::$create->closure(
            params: pipe(
                $this->boundTemplate,
                PsalmApi::$create->genericObject(Bindable::class),
                $this->liftF,
                PsalmApi::$create->param('context'),
            ),
            return: pipe($this->compiledBindable(), $this->liftF),
        );
    }

    private function getNextReturnType(string $propertyName): Union
    {
        $property = $this->templates->createTemplate('T'.($this->functionOffset++));
        $this->previousProperties[$propertyName] = $property;

        return match ($this->type) {
            BindLetType::LET => pipe($property, PsalmApi::$create->union(...)),
            BindLetType::BIND => pipe($property, PsalmApi::$create->union(...), $this->liftF),
        };
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
