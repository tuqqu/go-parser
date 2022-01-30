<?php

declare(strict_types=1);

const CONFIG = new PhpCsFixer\Config();
const RULES = [
    '@PSR2' => true,
    '@PSR12' => true,
    'strict_param' => true,
    'braces' => false,
    'single_import_per_statement' => false,
    'ternary_operator_spaces' => false,
    'array_syntax' => ['syntax' => 'short'],
];

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

CONFIG->setRules(RULES);
CONFIG->setFinder($finder);

return CONFIG;
