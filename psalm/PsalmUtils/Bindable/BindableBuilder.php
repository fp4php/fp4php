<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Bindable;

use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

interface BindableBuilder
{
    public function getNextFunction(string $forProperty): FunctionLikeParameter;

    /**
     * @return list<TTemplateParam>
     */
    public function getTemplates(): array;

    public function getReturnType(): Union;
}
