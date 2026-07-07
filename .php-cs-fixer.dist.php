<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'          => true,
        '@PHP84Migration' => true,
        // PHPStan 1.12 cannot parse `new Foo()->method()` without parens
        'new_expression_parentheses' => ['use_parentheses' => true],
    ])
    ->setFinder($finder);
