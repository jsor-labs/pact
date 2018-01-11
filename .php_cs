<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
;

return PhpCsFixer\Config::create()
    ->setRules(array(
        '@PSR1' => true,
        '@PSR2' => true,
        'array_syntax' => array('syntax' => 'long'),
        'braces' => array(
            'allow_single_line_closure' => true
        ),
        'cast_spaces' => true,
        'combine_consecutive_unsets' => true,
        'function_to_constant' => true,
        'native_function_invocation' => true,
        'no_multiline_whitespace_before_semicolons' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'no_whitespace_before_comma_in_array' => true,
        'no_whitespace_in_blank_line' => true,
        'non_printable_character' => true,
        'normalize_index_brace' => true,
        'ordered_imports' => true,
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => true,
        'phpdoc_summary' => true,
        'phpdoc_types' => true,
        'psr4' => true,
        'return_type_declaration' => array('space_before' => 'none'),
        'short_scalar_cast' => true,
        'single_blank_line_before_namespace' => true,
    ))
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
