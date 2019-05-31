<?php


$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('*.php')
    ->exclude('vendor')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return PhpCsFixer\Config::create()
    ->setRiskyAllowed(true)
    ->setRules(array(
        '@PSR2'                => true,
        'strict_param'         => true,
        'declare_strict_types' => true,
        'array_syntax'         => ['syntax' => 'short'],
        'single_quote'         => true,
        'native_function_invocation'=>true,
    ))
    ->setFinder($finder)
    ->setUsingCache(true);
