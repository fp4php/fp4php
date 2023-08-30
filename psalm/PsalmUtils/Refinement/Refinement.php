<?php

declare(strict_types=1);

namespace Fp4\PHP\PsalmIntegration\PsalmUtils\Refinement;

use Fp4\PHP\Module\ArrayDictionary as D;
use Fp4\PHP\Module\ArrayList as L;
use Fp4\PHP\Module\Evidence as Ev;
use Fp4\PHP\Module\Option as O;
use Fp4\PHP\Module\Str as S;
use Fp4\PHP\Module\Tuple as T;
use Fp4\PHP\PsalmIntegration\PsalmUtils\FunctionType;
use Fp4\PHP\PsalmIntegration\PsalmUtils\PsalmApi;
use Fp4\PHP\Type\Option;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Return_;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Storage\Assertion;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;

use function count;
use function Fp4\PHP\Module\Evidence\proveNonEmptyArray;
use function Fp4\PHP\Module\Combinator\pipe;

/**
 * @psalm-type PsalmAssertions = array<string, list<list<Assertion>>>
 */
final class Refinement
{
    public function __construct(
        public readonly FunctionType $type,
        public readonly RefineTypeParams $typeParams,
        public readonly FunctionLike $predicate,
        public readonly StatementsAnalyzer $source,
        public readonly Context $context,
    ) {
    }

    public function refine(): RefineTypeParams
    {
        return new RefineTypeParams(
            key: pipe($this->refineArgumentType(for: 'key'), O\getOrElse($this->typeParams->key)),
            value: pipe($this->refineArgumentType(for: 'value'), O\getOrElse($this->typeParams->value)),
        );
    }

    /**
     * @param 'key'|'value' $for
     * @return Option<Union>
     */
    private function refineArgumentType(string $for): Option
    {
        // reconcileKeyedTypes takes it by ref
        $changed_var_ids = [];

        return pipe(
            O\bindable(),
            O\bind(
                return: fn() => self::getPredicateSingleReturn($this),
                assertions: fn($i) => self::collectAssertions($this, $i->return),
                argument: fn() => 'value' === $for
                    ? self::getValueArgumentFromPredicate($this)
                    : self::getKeyArgumentFromPredicate($this),
                reconciled_types: fn($i) => O\some(
                    T\from(Reconciler::reconcileKeyedTypes(
                        new_types: $i->assertions,
                        active_new_types: $i->assertions,
                        existing_types: [
                            $i->argument => 'value' === $for
                                ? $this->typeParams->value
                                : $this->typeParams->key,
                        ],
                        existing_references: [],
                        changed_var_ids: $changed_var_ids,
                        referenced_var_ids: [$i->argument => true],
                        statements_analyzer: $this->source,
                        template_type_map: $this->source->getTemplateTypeMap() ?: [],
                        code_location: new CodeLocation($this->source, $i->return),
                    )),
                ),
            ),
            O\flatMap(fn($i) => pipe(
                $i->reconciled_types[0],
                D\get($i->argument),
            )),
        );
    }

    /**
     * @return Option<non-empty-string>
     */
    private static function getKeyArgumentFromPredicate(self $refinement): Option
    {
        return pipe(
            O\fromNullable(
                FunctionType::KeyValue === $refinement->type
                    ? L\fromIterable($refinement->predicate->getParams())
                    : null,
            ),
            O\flatMap(L\first(...)),
            O\map(fn(Param $param) => $param->var),
            O\filterOf(Variable::class),
            O\map(fn(Variable $v) => $v->name),
            O\flatMap(Ev\proveString(...)),
            O\map(S\prepend('$')),
        );
    }

    /**
     * @return Option<non-empty-string>
     */
    private static function getValueArgumentFromPredicate(self $refinement): Option
    {
        return pipe(
            $refinement->predicate->getParams(),
            L\fromIterable(...),
            O\some(...),
            O\flatMap(L\last(...)),
            O\map(fn(Param $param) => $param->var),
            O\filterOf(Variable::class),
            O\flatMap(fn(Variable $variable) => Ev\proveString($variable->name)),
            O\map(S\prepend('$')),
        );
    }

    /**
     * @return Option<Expr>
     */
    private static function getPredicateSingleReturn(self $refinement): Option
    {
        return pipe(
            O\fromNullable($refinement->predicate->getStmts()),
            O\filter(fn($stmts) => 1 === count($stmts)),
            O\flatMap(fn($stmts) => pipe(
                L\fromIterable($stmts),
                L\first(...),
                O\filterOf(Return_::class),
                O\flatMap(fn(Return_ $return) => O\fromNullable($return->expr)),
            )),
        );
    }

    /**
     * @psalm-return Option<PsalmAssertions>
     */
    private static function collectAssertions(self $refinement, Expr $return): Option
    {
        $cond_object_id = spl_object_id($return);

        $truths = Algebra::getTruthsFromFormula(
            clauses: FormulaGenerator::getFormula(
                conditional_object_id: $cond_object_id,
                creating_object_id: $cond_object_id,
                conditional: $return,
                this_class_name: $refinement->context->self,
                source: $refinement->source,
                codebase: PsalmApi::$codebase,
            ),
            creating_conditional_id: $cond_object_id,
        );

        return proveNonEmptyArray($truths);
    }
}
