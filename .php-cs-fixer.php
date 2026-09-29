<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__ . '/src/PayWire')
    ->exclude('vendor');

return new PhpCsFixer\Config()
    ->setFinder($finder)
    ->setRules(
        [
            '@Symfony' => true,
            'strict_param' => false,
            'concat_space' => ['spacing' => 'one'],
            'phpdoc_align' => ['align' => 'left'],
            'phpdoc_summary' => false,
            'void_return' => false,
            'phpdoc_var_without_name' => false,
            'phpdoc_to_comment' => false,
            'single_line_throw' => false,
            'native_function_invocation' => ['include' => ['@internal'], 'scope' => 'namespaced', 'strict' => true],
            'function_declaration' => false,
            'phpdoc_separation' => ['skip_unlisted_annotations' => true],
            'trailing_comma_in_multiline' => ['elements' => ['arrays', 'parameters']],
            'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        ]
    )
    ->setRiskyAllowed(true);
