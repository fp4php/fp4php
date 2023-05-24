<?php

declare(strict_types=1);

namespace Fp4\PHP\Type;

/**
 * @template-covariant T of object
 * @psalm-immutable
 */
final class Bindable
{
    public function __construct(
        public readonly array $context = [],
    ) {
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __get(string $name): mixed
    {
        return $this->context[$name];
    }

    public function with(string $name, mixed $value): self
    {
        return new self([...$this->context, $name => $value]);
    }
}
