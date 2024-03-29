<?php

declare(strict_types=1);

const CONFIG = new PhpCsFixer\Config();
const RULES = [
    '@PER' => true,
    'strict_param' => true,
    'ternary_operator_spaces' => false,
    'no_break_comment' => false,
    'array_syntax' => ['syntax' => 'short'],
    'no_unused_imports' => true,
    'single_line_empty_body' => true,
    'statement_indentation' => false,
    'global_namespace_import' => [
        'import_classes' => true,
        'import_constants' => true,
        'import_functions' => true,
    ],
];

CONFIG->setRules(RULES);
CONFIG->setFinder(PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
);

return CONFIG;
