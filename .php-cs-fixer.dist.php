<?php

declare(strict_types=1);

const CONFIG = new PhpCsFixer\Config();
const RULES = [
    '@PSR12' => true,
    'strict_param' => true,
    'braces' => false,
    'ternary_operator_spaces' => false,
    'no_break_comment' => false,
    'array_syntax' => ['syntax' => 'short'],
];

CONFIG->setRules(RULES);
CONFIG->setFinder(PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/bin')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
);

return CONFIG;
