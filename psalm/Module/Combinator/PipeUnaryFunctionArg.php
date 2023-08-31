<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\Module\Combinator;

use Fp4\PHP\ArrayList as L;
use Fp4\PHP\Option as O;
use PhpParser\Node\Arg;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Union;

use function Fp4\PHP\Combinator\pipe;

final class PipeUnaryFunctionArg
{
    public function __construct(
        public readonly Arg $node,
        public readonly Union $input,
        public readonly Union $output,
        public readonly TClosure|TCallable $original,
    ) {
    }

    /**
     * @return O\Option<self>
     */
    public static function from(Arg $node, TClosure|TCallable $unaryFunction): O\Option
    {
        return pipe(
            O\bindable(),
            O\bind(
                input: fn() => pipe(
                    L\first($unaryFunction->params ?? []),
                    O\flatMapNullable(fn(FunctionLikeParameter $p) => $p->type),
                ),
                output: fn() => O\fromNullable($unaryFunction->return_type),
            ),
            O\map(fn($i) => new self($node, $i->input, $i->output, $unaryFunction)),
        );
    }
}
