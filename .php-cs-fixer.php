<?php

declare(strict_types=1);

require_once __DIR__.'/vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/psalm',
    ])
;

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP80Migration:risky' => true,
        '@PHP81Migration' => true,
        '@PHP82Migration' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@PHPUnit84Migration:risky' => true,
        '@PSR12' => true,
        '@PSR12:risky' => true,
        'blank_line_before_statement' => ['statements' => [
            'continue',
            'declare',
            'default',
            'return',
            'throw',
            'try',
            'while',
        ]],
        'braces' => [
            'allow_single_line_anonymous_class_with_empty_body' => true,
            'allow_single_line_closure' => true,
        ],
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'comment_to_phpdoc' => ['ignored_tags' => ['fixme']],
        'date_time_immutable' => true,
        'final_class' => true,
        'final_public_method_for_abstract_class' => true,
        'no_superfluous_phpdoc_tags' => ['remove_inheritdoc' => true],
        'nullable_type_declaration_for_default_null_value' => true,
        'global_namespace_import' => [
            'import_constants' => true,
            'import_functions' => true,
            'import_classes' => true,
        ],
        'function_declaration' => [
            'closure_fn_spacing' => 'none',
            'closure_function_spacing' => 'none',
        ],
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
        'phpdoc_align' => false,
        'phpdoc_separation' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_types_order' => false,
        'no_empty_statement' => false,
        'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['arrays', 'arguments', 'parameters']],
        'yoda_style' => ['equal' => true, 'identical' => true, 'less_and_greater' => false],
    ])
;
