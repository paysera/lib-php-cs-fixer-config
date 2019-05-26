<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Config;

use Paysera\PhpCsFixerConfig\Fixer\IgnorableFixerDecorator;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\DefaultValuesInConstructorFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Overwritten\BracesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\DocBlockWhitespaceFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\CheckingExplicitlyFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\LogicalOperatorsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\FluidInterfaceFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\CommentStylesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocContentsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\FullNamesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ClassNamingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\InterfaceNamingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocOnPropertiesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\MethodNamingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\PropertyNamingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\CallingParentConstructorFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ChainedMethodCallsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ComparingToBooleanFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ComparingToNullFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\DateTimeFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Basic\GlobalsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\NamespacesAndUseStatementsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Basic\SingleClassPerFileFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\SplittingInSeveralLinesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ReturnAndArgumentTypesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ThrowBaseExceptionFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TraitsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\DirectoryAndNamespaceFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TypeHintingArgumentsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TypeHintingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\UnnecessaryVariablesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\VisibilityPropertiesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\StaticMethodsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\MagicMethodsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\AssignmentsInConditionsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\UnnecessaryStructuresFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ReusingVariablesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ConditionResultsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\VoidResultFixer;
use Paysera\PhpCsFixerConfig\Fixer\PSR1\ClassConstantUpperCaseFixer;
use Paysera\PhpCsFixerConfig\Fixer\PSR1\ClassNameStudlyCapsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PSR1\FileSideEffectsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PSR1\FunctionNameCamelCaseFixer;
use Paysera\PhpCsFixerConfig\Fixer\PSR2\LineLengthFixer;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Fixer\FixerInterface;
use RuntimeException;

/**
 * @api
 */
class PayseraConventionsConfig extends Config
{
    /**
     * @var null|array
     */
    private $migrationModeRules;

    public function __construct()
    {
        parent::__construct('paysera_conventions');
        $this->setUsingCache(false);
        $this->registerCustomFixers($this->decorateWithIgnorable([
            new LineLengthFixer(),
            new ClassConstantUpperCaseFixer(),
            new ClassNameStudlyCapsFixer(),
            new FunctionNameCamelCaseFixer(),
            new FileSideEffectsFixer(),
            new GlobalsFixer(),
            new SingleClassPerFileFixer(),
            new ChainedMethodCallsFixer(),
            new ClassNamingFixer(),
            new DirectoryAndNamespaceFixer(),
            new FullNamesFixer(),
            new InterfaceNamingFixer(),
            new MethodNamingFixer(),
            new NamespacesAndUseStatementsFixer(),
            new PropertyNamingFixer(),
            new SplittingInSeveralLinesFixer(),
            new CommentStylesFixer(),
            new FluidInterfaceFixer(),
            new PhpDocContentsFixer(),
            new PhpDocOnPropertiesFixer(),
            new AssignmentsInConditionsFixer(),
            new CallingParentConstructorFixer(),
            new CheckingExplicitlyFixer(),
            new ComparingToBooleanFixer(),
            new ComparingToNullFixer(),
            new ConditionResultsFixer(),
            new DateTimeFixer(),
            new MagicMethodsFixer(),
            new ReturnAndArgumentTypesFixer(),
            new ReusingVariablesFixer(),
            new StaticMethodsFixer(),
            new ThrowBaseExceptionFixer(),
            new TraitsFixer(),
            new TypeHintingArgumentsFixer(),
            new TypeHintingFixer(),
            new UnnecessaryStructuresFixer(),
            new UnnecessaryVariablesFixer(),
            new VisibilityPropertiesFixer(),
            new LogicalOperatorsFixer(),
            new VoidResultFixer(),
            new DocBlockWhitespaceFixer(),
            new BracesFixer(),
            new DefaultValuesInConstructorFixer(),
        ]));
    }

    private function decorateWithIgnorable(array $fixers)
    {
        return array_map(function (FixerInterface $fixer) {
            return new IgnorableFixerDecorator($fixer);
        }, $fixers);
    }

    public function setDefaultFinder(
        array $in = ['src'],
        array $exclude = ['tests', 'Tests', 'test', 'Test']
    ) {
        $finder = Finder::create()
            ->in($in)
            ->exclude($exclude)
        ;

        $this->setFinder($finder);

        return $this;
    }

    public function enableMigrationMode(array $rules)
    {
        $this->migrationModeRules = $rules;

        return $this;
    }

    public function getRules()
    {
        $rules = parent::getRules();
        if ($this->migrationModeRules === null) {
            return $rules;
        }

        $this->validateMigrationMode($rules);

        $disabledRules = array_keys(array_filter($this->migrationModeRules, function ($item) {
            return $item === false;
        }));

        return array_merge(
            $rules,
            array_combine($disabledRules, array_fill(0, count($disabledRules), false))
        );
    }

    private function validateMigrationMode(array $rules)
    {
        $enabledRules = array_keys(array_filter($rules));
        $rulesUnconfigured = array_diff($enabledRules, array_keys($this->migrationModeRules));
        if (count($rulesUnconfigured) > 0) {
            $configuration = "    '" . implode("' => false,\n    '", $rulesUnconfigured) . "' => false,\n";
            throw new RuntimeException(sprintf(
                "You have to configure all rules for migration, please configure these:\n\n%s",
                $configuration
            ));
        }
    }

    public function setSafeRules(array $rules = [])
    {
        return $this
            ->setRiskyAllowed(false)
            ->setRules(array_merge(
                $this->getSafeRules(),
                $rules
            ))
        ;
    }

    public function setRiskyRules(array $rules = [])
    {
        return $this
            ->setRiskyAllowed(true)
            ->setRules(array_merge(
                $this->getSafeRules(),
                $this->getRiskyRules(),
                $rules
            ))
        ;
    }

    public function setRecommendedRules(array $rules = [])
    {
        return $this
            ->setRiskyAllowed(true)
            ->setRules(array_merge(
                $this->getSafeRules(),
                $this->getRiskyRules(),
                $this->getRecommendedRules(),
                $rules
            ))
        ;
    }

    private function getRecommendedRules()
    {
        return [
            'Paysera/psr_1_file_side_effects' => true,
            'Paysera/psr_2_line_length' => ['soft_limit' => 120],
            'Paysera/php_basic_basic_globals' => true,
            'Paysera/php_basic_basic_single_class_per_file' => true,
            'Paysera/php_basic_code_style_class_naming' => true,
            'Paysera/php_basic_code_style_directory_and_namespace' => true,
            'Paysera/php_basic_code_style_method_naming' => true,
            'Paysera/php_basic_code_style_property_naming' => true,
            'Paysera/php_basic_comment_php_doc_contents' => true,
            'Paysera/php_basic_comment_php_doc_on_properties' => true,
            'Paysera/php_basic_feature_assignments_in_conditions' => true,
            'Paysera/php_basic_feature_date_time' => true,
            'Paysera/php_basic_feature_magic_methods' => true,
            'Paysera/php_basic_feature_return_and_argument_types' => true,
            'Paysera/php_basic_feature_reusing_variables' => true,
            'Paysera/php_basic_feature_static_methods' => true,
            'Paysera/php_basic_feature_throw_base_exception' => true,
            'Paysera/php_basic_feature_traits' => true,
            'Paysera/php_basic_feature_unnecessary_structures' => true,
            'Paysera/php_basic_feature_visibility_properties' => true,
            'Paysera/php_basic_feature_void_result' => true,
        ];
    }

    private function getRiskyRules()
    {
        return [
            // Symfony:risky
            'dir_constant' => true,
            'ereg_to_preg' => true,
            'function_to_constant' => true,
            'modernize_types_casting' => true,
            'no_alias_functions' => true,
            'no_homoglyph_names' => true,
            'non_printable_character' => [
                'use_escape_sequences_in_strings' => false,
            ],
            'php_unit_construct' => true,
            'psr4' => true,
            'silenced_deprecation_error' => true,
            // exceptions
            'is_null' => ['use_yoda_style' => false],
            'self_accessor' => false,

            // other base rules
            'no_unreachable_default_argument_value' => true,
            'strict_comparison' => true,
            'strict_param' => true,

            // custom rules
            'Paysera/php_basic_feature_checking_explicitly' => true,
            'Paysera/psr_1_class_constant_upper_case' => true,
            'Paysera/psr_1_class_name_studly_caps' => true,
            'Paysera/psr_1_function_name_camel_case' => true,
            'Paysera/php_basic_code_style_full_names' => true,
            'Paysera/php_basic_code_style_interface_naming' => true,
            'Paysera/php_basic_code_style_namespaces_and_use_statements' => true,
            'Paysera/php_basic_feature_calling_parent_constructor' => true,
            'Paysera/php_basic_feature_logical_operators' => true,
            'Paysera/php_basic_feature_type_hinting_arguments' => true,
            'Paysera/php_basic_feature_unnecessary_variables' => true,
            'Paysera/php_basic_feature_comparing_to_boolean' => true,
            'Paysera/php_basic_code_style_default_values_in_constructor' => true,
            'Paysera/php_basic_feature_type_hinting' => true,
        ];
    }

    private function getSafeRules()
    {
        return [
            // PSR1, PSR2 and Symfony
            'encoding' => true,
            'full_opening_tag' => true,
            'blank_line_after_namespace' => true,
            'elseif' => true,
            'function_declaration' => true,
            'indentation_type' => true,
            'line_ending' => true,
            'lowercase_constants' => true,
            'lowercase_keywords' => true,
            'no_break_comment' => true,
            'no_closing_tag' => true,
            'no_spaces_after_function_name' => true,
            'no_spaces_inside_parenthesis' => true,
            'no_trailing_whitespace' => true,
            'no_trailing_whitespace_in_comment' => true,
            'single_blank_line_at_eof' => true,
            'single_import_per_statement' => true,
            'single_line_after_imports' => true,
            'switch_case_semicolon_to_colon' => true,
            'switch_case_space' => true,
            'visibility_required' => true,

            'binary_operator_spaces' => true,
            'class_attributes_separation' => ['elements' => ['method']],
            'class_definition' => [
                'multiLineExtendsEachSingleLine' => true,
                'singleItemSingleLine' => true,
            ],
            'declare_equal_normalize' => true,
            'function_typehint_space' => true,
            'include' => true,
            'lowercase_cast' => true,
            'magic_constant_casing' => true,
            'method_argument_space' => true,
            'native_function_casing' => true,
            'new_with_braces' => true,
            'no_blank_lines_after_class_opening' => true,
            'no_blank_lines_after_phpdoc' => true,
            'no_empty_comment' => true,
            'no_empty_phpdoc' => true,
            'no_empty_statement' => true,
            'no_leading_import_slash' => true,
            'no_leading_namespace_whitespace' => true,
            'no_mixed_echo_print' => ['use' => 'echo'],
            'no_multiline_whitespace_around_double_arrow' => true,
            'no_short_bool_cast' => true,
            'no_spaces_around_offset' => true,
            'no_trailing_comma_in_list_call' => true,
            'no_trailing_comma_in_singleline_array' => true,
            'no_unneeded_curly_braces' => true,
            'no_unneeded_final_method' => true,
            'no_unused_imports' => true,
            'no_whitespace_before_comma_in_array' => true,
            'no_whitespace_in_blank_line' => true,
            'normalize_index_brace' => true,
            'object_operator_without_whitespace' => true,
            'php_unit_fqcn_annotation' => true,
            'phpdoc_annotation_without_dot' => true,
            'phpdoc_indent' => true,
            'phpdoc_no_access' => true,
            'phpdoc_no_empty_return' => true,
            'phpdoc_no_package' => true,
            'phpdoc_no_useless_inheritdoc' => true,
            'phpdoc_return_self_reference' => true,
            'phpdoc_scalar' => true,
            'phpdoc_single_line_var_spacing' => true,
            'phpdoc_trim' => true,
            'phpdoc_types' => true,
            'phpdoc_var_without_name' => true,
            'protected_to_private' => true,
            'return_type_declaration' => true,
            'semicolon_after_instruction' => true,
            'short_scalar_cast' => true,
            'single_blank_line_before_namespace' => true,
            'single_class_element_per_statement' => true,
            'single_line_comment_style' => [
                'comment_types' => ['hash'],
            ],
            'single_quote' => true,
            'space_after_semicolon' => [
                'remove_in_empty_for_expressions' => true,
            ],
            'standardize_increment' => true,
            'standardize_not_equals' => true,
            'ternary_operator_spaces' => true,
            'trailing_comma_in_multiline_array' => true,
            'trim_array_spaces' => true,
            'unary_operator_spaces' => true,
            'whitespace_after_comma_in_array' => true,

            // exceptions
            'increment_style' => ['style' => 'post'],
            'blank_line_after_opening_tag' => false,
            'blank_line_before_statement' => null,
            'cast_spaces' => false,
            'concat_space' => ['spacing' => 'one'],
            'no_singleline_whitespace_before_semicolons' => false,
            'phpdoc_align' => false,
            'phpdoc_separation' => false,
            'phpdoc_summary' => false,
            'yoda_style' => false,
            'phpdoc_to_comment' => false,
            'phpdoc_no_alias_tag' => false,
            'phpdoc_inline_tag' => false,
            'no_unneeded_control_parentheses' => false, // works too aggressively with large structures
            'no_extra_blank_lines' => ['tokens' => [ // don't use curly_brace_block to allow splitting elseif blocks
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use',
            ]],
            'Paysera/php_basic_braces' => [ // temporary overwritten from "braces" fixer
                'allow_single_line_closure' => true,
            ],

            // other base rules
            'no_useless_else' => true,
            'blank_line_before_return' => false,
            'phpdoc_order' => false,
            'phpdoc_add_missing_param_annotation' => [
                'only_untyped' => false,
            ],
            'pre_increment' => false,
            'no_multiline_whitespace_before_semicolons' => false,
            'ordered_imports' => false,
            'array_syntax' => ['syntax' => 'short'],
            'general_phpdoc_annotation_remove' => ['author', 'namespace', 'date', 'inheritdoc', 'package'],
            'header_comment' => ['header' => ''],
            'heredoc_to_nowdoc' => true,
            'linebreak_after_opening_tag' => true,
            'no_useless_return' => true,
            'strict_comparison' => false,
            'strict_param' => false,
            'ordered_class_elements' => [
                'use_trait',
                'constant',
                'property_static',
                'property',
                'construct',
                'destruct',
                'magic',
                'method_static',
                'method',
            ],

            // other custom rules
            'Paysera/php_basic_code_style_chained_method_calls' => true,
            'Paysera/php_basic_code_style_splitting_in_several_lines' => true,
            'Paysera/php_basic_comment_comment_styles' => true,
            'Paysera/php_basic_comment_fluid_interface' => true,
            'Paysera/php_basic_feature_comparing_to_null' => true,
            'Paysera/php_basic_feature_condition_results' => true,
            'Paysera/php_basic_code_style_doc_block_whitespace' => true,
        ];
    }
}
