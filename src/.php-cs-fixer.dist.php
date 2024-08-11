<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()->in('.');

$rules = [
    '@PSR2' => true,
    'array_syntax' => ['syntax' => 'short'],
    'concat_space' => ['spacing' => 'one'],
    'include' => true,
    'new_with_braces' => true,
    'no_empty_statement' => true,
    'no_extra_blank_lines' => true,
    'no_leading_import_slash' => true,
    'no_leading_namespace_whitespace' => true,
    'no_multiline_whitespace_around_double_arrow' => true,
    'multiline_whitespace_before_semicolons' => true,
    'no_singleline_whitespace_before_semicolons' => true,
    'no_trailing_comma_in_singleline_array' => true,
    'no_unused_imports' => true,
    'no_whitespace_in_blank_line' => true,
    'object_operator_without_whitespace' => true,
    'ordered_imports' => true,
    'standardize_not_equals' => true,
    'ternary_operator_spaces' => true,
    'no_empty_phpdoc' => true,
    'no_superfluous_phpdoc_tags' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_trim' => true,
    'void_return' => true,
    'phpdoc_no_empty_return' => true
];

$config = new PhpCsFixer\Config();

return $config->setRiskyAllowed(true)->setRules($rules)->setFinder($finder);
