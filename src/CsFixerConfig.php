<?php

declare(strict_types=1);

/*
 * This file is part of the FULLHAUS PHP-CS-Fixer configuration.
 *
 * (c) 2024-2026 FULLHAUS GmbH
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace FULLHAUS\CodingStandards;

use FULLHAUS\CodingStandards\Fixer\ArraySpacingFixer;
use PhpCsFixer\Config;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

class CsFixerConfig extends Config implements CsFixerConfigInterface
{
    private const DEFAULT_NAME = 'FULLHAUS';

    private const EXCLUDED_DIRECTORIES = [
        '.build',
        '.Build',
        'typo3temp',
        'var',
        'vendor',
    ];

    private const EXCLUDED_PATHS = [
        'config/system/settings.php',
    ];

    /**
     * @var array<string, array<string, mixed>|bool>
     */
    protected const RULES = [
        '@DoctrineAnnotation' => true,
        // @todo: Switch to @PER-CS2x0 once php-cs-fixer's todo list is done: https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/issues/7247
        '@PER-CS1x0' => true,
        'array_indentation' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'assign_null_coalescing_to_coalesce_equal' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'case',
                'continue',
                'declare',
                'default',
                'do',
                'exit',
                'for',
                'foreach',
                'goto',
                'if',
                'include',
                'include_once',
                'phpdoc',
                'require',
                'require_once',
                'return',
                'switch',
                'throw',
                'try',
                'while',
                'yield',
                'yield_from',
            ],
        ],
        'cast_spaces' => [
            'space' => 'none',
        ],
        // @todo: Can be dropped once we enable @PER-CS2x0
        'concat_space' => [
            'spacing' => 'one',
        ],
        'declare_equal_normalize' => [
            'space' => 'none',
        ],
        'declare_parentheses' => true,
        'dir_constant' => true,
        // @todo: Can be dropped once we enable @PER-CS2x0
        'function_declaration' => [
            'closure_fn_spacing' => 'none',
        ],
        'function_to_constant' => [
            'functions' => [
                'get_called_class',
                'get_class',
                'get_class_this',
                'php_sapi_name',
                'phpversion',
                'pi',
            ],
        ],
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
            'leading_backslash_in_global_namespace' => true,
        ],
        'global_namespace_import' => [
            'import_classes' => false,
            'import_constants' => false,
            'import_functions' => false,
        ],
        'list_syntax' => [
            'syntax' => 'short',
        ],
        // @todo: Can be dropped once we enable @PER-CS2x0
        'method_argument_space' => true,
        'modernize_strpos' => true,
        'modernize_types_casting' => true,
        'native_function_casing' => true,
        'new_with_parentheses' => true,
        'no_alias_functions' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_phpdoc' => true,
        'no_empty_statement' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'attribute',
                'break',
                'case',
                'comma',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'return',
                'square_brace_block',
                'switch',
                'throw',
                'use',
                'use_trait',
            ],
        ],
        'no_leading_namespace_whitespace' => true,
        'no_null_property_initialization' => true,
        'no_short_bool_cast' => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_superfluous_elseif' => true,
        'no_trailing_comma_in_singleline' => true,
        'no_unneeded_control_parentheses' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_nullsafe_operator' => true,
        'nullable_type_declaration' => [
            'syntax' => 'union',
        ],
        'nullable_type_declaration_for_default_null_value' => true,
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant',
                'property',
            ],
        ],
        'ordered_imports' => [
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
            'sort_algorithm' => 'alpha',
        ],
        'ordered_types' => [
            'case_sensitive' => true,
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'alpha',
        ],
        'php_unit_construct' => [
            'assertions' => [
                'assertEquals',
                'assertSame',
                'assertNotEquals',
                'assertNotSame',
            ],
        ],
        'php_unit_mock_short_will_return' => true,
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'self',
            'methods' => [
                'any' => 'this',
                'atLeast' => 'this',
                'atLeastOnce' => 'this',
                'atMost' => 'this',
                'exactly' => 'this',
                'never' => 'this',
                'onConsecutiveCalls' => 'this',
                'once' => 'this',
                'returnArgument' => 'this',
                'returnCallback' => 'this',
                'returnSelf' => 'this',
                'returnValue' => 'this',
                'returnValueMap' => 'this',
                'throwException' => 'this',
            ],
        ],
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => true,
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'phpdoc_trim' => true,
        'phpdoc_types' => true,
        'phpdoc_types_order' => [
            'null_adjustment' => 'always_last',
            'sort_algorithm' => 'none',
        ],
        'return_type_declaration' => [
            'space_before' => 'none',
        ],
        'simplified_null_return' => true,
        'single_line_comment_style' => [
            'comment_types' => [
                'hash',
            ],
        ],
        // @todo: Can be dropped once we enable @PER-CS2x0
        'single_line_empty_body' => true,
        'single_quote' => true,
        'single_space_around_construct' => true,
        'ternary_to_null_coalescing' => true,
        'trailing_comma_in_multiline' => [
            'elements' => [
                'arguments',
                'arrays',
                'match',
                'parameters',
            ],
        ],
        'type_declaration_spaces' => [
            'elements' => [
                'constant',
                'function',
                'property',
            ],
        ],
        'unary_operator_spaces' => true,
        'whitespace_after_comma_in_array' => [
            'ensure_single_space' => true,
        ],
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false,
        ],
    ];

    public function __construct(string $name = self::DEFAULT_NAME)
    {
        parent::__construct($name);
    }

    public static function create(): static
    {
        $config = new static();
        $config
            ->setParallelConfig(ParallelConfigFactory::detect())
            ->setRiskyAllowed(true)
            ->registerCustomFixers([ new ArraySpacingFixer() ])
            ->setRules(array_merge(static::RULES, [
                'FULLHAUS/array_spacing' => true,
            ]));

        $config->getFinder()
            ->exclude(self::EXCLUDED_DIRECTORIES)
            ->ignoreVCSIgnored(true)
            ->notPath(self::EXCLUDED_PATHS);

        return $config;
    }

    /**
     * @param array<string, mixed> $rules
     */
    public function addRules(array $rules): static
    {
        $mergedRules = array_replace_recursive($this->getRules(), $rules);
        $this->setRules($mergedRules);

        return $this;
    }
}
