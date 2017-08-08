<?php

namespace Paysera\PhpCsFixerConfig\Config;

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
            ]
        );
    }
}
