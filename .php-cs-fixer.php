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
            'declare_strict_types' => true,
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
            'ordered_class_elements' => [
                'order' => ['use_trait', 'case', 'constant_public', 'constant_protected', 'constant_private', 'property_public', 'property_protected', 'property_private', 'construct', 'destruct', 'magic', 'phpunit', 'method_public_static', 'method_public', 'method_protected_static', 'method_protected', 'method_private_static', 'method_private'],
            ],
        ]
    )
    ->setRiskyAllowed(true);
