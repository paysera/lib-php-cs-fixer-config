<?php

namespace Paysera\PhpCsFixerConfig\Config;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\DocBlockWhitespaceFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocNecessityFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\CheckingExplicitlyFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\LogicalOperatorsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\StrictComparisonOperatorsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\FluidInterfaceFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\CommentStylesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocContentsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\FullNamesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ClassNamingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\InterfaceNamingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocOnMethodsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocOnPropertiesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\MethodNamingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\PropertyNamingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\CallingParentConstructorFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ChainedMethodCallsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ClassConstructorsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ComparisonOrderFixer;
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
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\FunctionIsNullFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TypeHintingArgumentsFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TypeHintingFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\UnnecessaryVariablesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\VisibilityPropertiesFixer;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\FunctionCountFixer;
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

class PayseraConventionsConfig extends Config
{
    public function __construct()
    {
        parent::__construct('paysera_conventions');
        $this->setUsingCache(false);
        $this->registerCustomFixers(
            [
                new LineLengthFixer(),
                new ClassConstantUpperCaseFixer(),
                new ClassNameStudlyCapsFixer(),
                new FunctionNameCamelCaseFixer(),
                new FileSideEffectsFixer(),
                new GlobalsFixer(),
                new SingleClassPerFileFixer(),
                new ChainedMethodCallsFixer(),
                new ClassConstructorsFixer(),
                new ClassNamingFixer(),
                new ComparisonOrderFixer(),
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
                new PhpDocOnMethodsFixer(),
                new PhpDocOnPropertiesFixer(),
                new AssignmentsInConditionsFixer(),
                new CallingParentConstructorFixer(),
                new CheckingExplicitlyFixer(),
                new ComparingToBooleanFixer(),
                new ComparingToNullFixer(),
                new ConditionResultsFixer(),
                new DateTimeFixer(),
                new FunctionCountFixer(),
                new FunctionIsNullFixer(),
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
                new StrictComparisonOperatorsFixer(),
                new LogicalOperatorsFixer(),
                new VoidResultFixer(),
                new DocBlockWhitespaceFixer(),
                new PhpDocNecessityFixer(),
            ]
        );
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
            'Paysera/php_basic_feature_type_hinting' => true,
            'Paysera/php_basic_feature_visibility_properties' => true,
            'Paysera/php_basic_feature_void_result' => true,
        ];
    }

    private function getRiskyRules()
    {
        return [
            'Paysera/php_basic_feature_checking_explicitly' => true,
            '@Symfony:risky' => true,
            'is_null' => ['use_yoda_style' => false],
            'no_unreachable_default_argument_value' => true,
            'strict_comparison' => true,
            'psr4' => true,
            'Paysera/psr_1_class_constant_upper_case' => true,
            'Paysera/psr_1_class_name_studly_caps' => true,
            'Paysera/psr_1_function_name_camel_case' => true,
            'Paysera/php_basic_code_style_comparison_order' => true,
            'Paysera/php_basic_code_style_full_names' => true,
            'Paysera/php_basic_code_style_interface_naming' => true,
            'Paysera/php_basic_code_style_namespaces_and_use_statements' => true,
            'Paysera/php_basic_feature_calling_parent_constructor' => true,
            'Paysera/php_basic_feature_logical_operators' => true,
            'Paysera/php_basic_feature_strict_comparison_operators' => true,
            'Paysera/php_basic_feature_type_hinting_arguments' => true,
            'Paysera/php_basic_feature_unnecessary_variables' => true,
        ];
    }

    private function getSafeRules()
    {
        $rules = [
            '@Symfony' => true,
            'blank_line_before_return' => false,
            'blank_line_after_opening_tag' => false,
            'cast_spaces' => false,
            'concat_space' => ['spacing' => 'one'],
            'no_singleline_whitespace_before_semicolons' => false,
            'phpdoc_align' => false,
            'phpdoc_order' => false,
            'phpdoc_separation' => false,
            'phpdoc_summary' => false,
            'phpdoc_add_missing_param_annotation' => false,
            'pre_increment' => false,
            'no_multiline_whitespace_before_semicolons' => false,
            'phpdoc_to_comment' => false,
            'phpdoc_no_alias_tag' => false,
            'phpdoc_inline_tag' => false,
            'ordered_imports' => false,
            'array_syntax' => ['syntax' => 'short'],
            'general_phpdoc_annotation_remove' => ['author', 'namespace', 'date'],
            'header_comment' => ['header' => ''],
            'phpdoc_no_package' => true,
            'heredoc_to_nowdoc' => true,
            'linebreak_after_opening_tag' => true,
            'no_useless_return' => true,
            'phpdoc_no_useless_inheritdoc' => true,
            'no_useless_else' => true,
            'semicolon_after_instruction' => true,
            'no_empty_comment' => true,
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
            'Paysera/php_basic_code_style_chained_method_calls' => true,
            'Paysera/php_basic_code_style_class_constructors' => true,
            'Paysera/php_basic_code_style_splitting_in_several_lines' => true,
            'Paysera/php_basic_comment_comment_styles' => true,
            'Paysera/php_basic_comment_fluid_interface' => true,
            'Paysera/php_basic_comment_php_doc_on_methods' => true,
            'Paysera/php_basic_feature_comparing_to_boolean' => true,
            'Paysera/php_basic_feature_comparing_to_null' => true,
            'Paysera/php_basic_feature_condition_results' => true,
            'Paysera/php_basic_feature_function_count' => true,
            'Paysera/php_basic_feature_function_is_null' => true,
            'Paysera/php_basic_code_style_doc_block_whitespace' => true,
            'Paysera/php_basic_comment_php_doc_necessity' => true,
        ];

        if (class_exists('\PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer')) {
            $rules['yoda_style'] = false;
        }

        return $rules;
    }
}
