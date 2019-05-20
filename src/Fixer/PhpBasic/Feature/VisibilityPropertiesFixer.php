<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionClass;
use SplFileInfo;
use Exception;

final class VisibilityPropertiesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    const DYNAMIC_PROPERTIES_CONVENTION = 'PhpBasic convention 3.14.1: We avoid dynamic properties';
    const PUBLIC_PROPERTIES_CONVENTION = 'PhpBasic convention 3.14.1: We don’t use public properties';
    const PROTECTED_PROPERTIES_CONVENTION = 'PhpBasic convention 3.14.2: We prefer use private over protected properties';
    const VISIBILITY_CONVENTION = 'PhpBasic convention 3.14: We must define as properties';
    const THIS = '$this';

    /**
     * @var array
     */
    private $excludedParents;

    public function __construct()
    {
        parent::__construct();
        $this->excludedParents = [
            'Constraint',
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We don’t use public and dynamic properties. All used properties must be defined.
            
            We prefer private over protected as it constraints the scope - it’s easier to refactor,
            find usages, plan possible changes in code. Also IDE can warn about unused methods or properties.
            
            We use protected when we intend some property or method to be overwritten if necessary.
            ',
            [
                new CodeSample('
                <?php
                class Sample
                {
                    public $foo;
                    public static $bar;
                    protected $baz;
                    
                    public function __construct()
                    {
                        $this->a = "some dynamic property";
                    }
                    
                    public function someFunction()
                    {
                        $this->a = "dynamic property usage";
                    }
                    
                    public function anotherFunction()
                    {
                        $foo = new Sample();
                        $foo->createProperty(\'hello\', \'something\');
                    }
                    
                    public function createProperty($name, $value){
                        $this->{$name} = $value;
                    }
                }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_visibility_properties';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function getPriority()
    {
        // Should run before `OrderedClassElementsFixer` and after `NamespacesAndUseStatementsFixer`
        return 60;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_PUBLIC, T_PRIVATE, T_PROTECTED, T_VARIABLE, T_STRING]);
    }

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        if ($this->configuration['excluded_parents'] === true) {
            return;
        }
        if (isset($this->configuration['excluded_parents'])) {
            $this->excludedParents = $this->configuration['excluded_parents'];
        }
    }

    protected function createConfigurationDefinition()
    {
        $options = new FixerOptionBuilder(
            'excluded_parents',
            'Allows to set Parent class names where in children Classes it is allowed to use public or protected properties'
        );

        $options = $options
            ->setAllowedTypes(['array', 'bool'])
            ->getOption()
        ;

        return new FixerConfigurationResolverRootless('excluded_parents', [$options], $this->getName());
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $propertyExclusion = false;
        $propertyVariables = [];
        $classNamespace = null;

        foreach ($tokens as $key => $token) {
            $nextTokenIndex = $tokens->getNextMeaningfulToken($key);
            $previousTokenIndex = $tokens->getPrevMeaningfulToken($key);

            if ($token->isGivenKind(T_NAMESPACE)) {
                $index = $key + 1;
                $namespace = '';
                while (!$tokens[$index + 1]->equals(';')) {
                    $index++;
                    $namespace .= $tokens[$index]->getContent();
                }
                $classNamespace = $namespace;
            }

            if ($token->isGivenKind(T_CLASS)) {
                $classNamespace .= '\\' . $tokens[$nextTokenIndex]->getContent();
            }

            if ($token->isGivenKind(T_EXTENDS)) {
                while (!$tokens[$key]->equals('{')) {
                    $classNameToken = $tokens->getNextTokenOfKind($key, [[T_STRING]]);
                    if (
                        isset($classNameToken)
                        && in_array(
                            $tokens[$classNameToken]->getContent(),
                            $this->excludedParents,
                            true
                        )
                        && $tokens[$classNameToken + 1]->isWhitespace()
                    ) {
                        $propertyExclusion = true;
                    }
                    $key++;
                }
            }

            if ($token->isGivenKind(T_VARIABLE)) {
                $propertyVariable = $this->getPropertyVariable($tokens, $key, $propertyExclusion);
                if ($propertyVariable !== null) {
                    $propertyVariables[] = $propertyVariable;
                }

                $previousTokenIndex = $tokens->getPrevNonWhitespace($key);
                $previousPreviousTokenIndex = $tokens->getPrevNonWhitespace($previousTokenIndex);
                if (
                    $tokens[$previousTokenIndex]->getContent() === '{'
                    && $tokens[$previousPreviousTokenIndex]->isGivenKind(T_OBJECT_OPERATOR)
                ) {
                    $this->insertComment(
                        $tokens,
                        $tokens->getNextTokenOfKind($key, [';']),
                        $tokens[$key]->getContent(),
                        self::DYNAMIC_PROPERTIES_CONVENTION
                    );
                }
            }

            if (
                $token->isGivenKind(T_STRING)
                && $tokens[$previousTokenIndex]->isGivenKind(T_OBJECT_OPERATOR)
                && (
                    $tokens[$nextTokenIndex]->isGivenKind(T_OBJECT_OPERATOR)
                    || $tokens[$key + 1]->isWhitespace()
                )
                && $tokens[$tokens->getPrevMeaningfulToken($previousTokenIndex)]->getContent() === self::THIS
            ) {
                $this->validateNotDefinedProperties(
                    $tokens,
                    $key,
                    $propertyVariables,
                    $token->getContent(),
                    $classNamespace
                );
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param array $propertyVariables
     * @param string $propertyName
     * @param string $classNamespace
     */
    private function validateNotDefinedProperties(
        Tokens $tokens,
        $key,
        $propertyVariables,
        $propertyName,
        $classNamespace
    ) {
        $variable = '$' . $propertyName;
        if (
            !in_array($variable, $propertyVariables, true)
            && !$this->isPropertyInAnotherClass($propertyName, $classNamespace)
        ) {
            $this->insertVariablePropertyWarning($tokens, $key, self::VISIBILITY_CONVENTION);
        }
    }

    /**
     * @param string $variableName
     * @param string $classNamespace
     * @return bool
     */
    private function isPropertyInAnotherClass($variableName, $classNamespace)
    {
        if (isset($classNamespace)) {
            try {
                $reflectionClass = new ReflectionClass($classNamespace);
                $properties = $reflectionClass->getProperties();
                foreach ($properties as $property) {
                    if ($property->getName() === $variableName) {
                        return true;
                    }
                }
            } catch (Exception $exception) {
            }
        }

        return false;
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param bool $propertyExclusion
     * @return string|null
     */
    private function getPropertyVariable(Tokens $tokens, $key, $propertyExclusion)
    {
        $previousTokenIndex = $tokens->getPrevMeaningfulToken($key);
        $previousPreviousTokenIndex = $tokens->getPrevMeaningfulToken($previousTokenIndex);
        if (
            $tokens[$previousTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            || (
                $tokens[$previousTokenIndex]->isGivenKind(T_STATIC)
                && $tokens[$previousPreviousTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            )
        ) {
            if (
                (
                    $tokens[$previousTokenIndex]->isGivenKind(T_PUBLIC)
                    || $tokens[$previousPreviousTokenIndex]->isGivenKind(T_PUBLIC)
                )
                && !$propertyExclusion
            ) {
                $this->insertVariablePropertyWarning($tokens, $key, self::PUBLIC_PROPERTIES_CONVENTION);
            }

            if (
                (
                    $tokens[$previousTokenIndex]->isGivenKind(T_PROTECTED)
                    || $tokens[$previousPreviousTokenIndex]->isGivenKind(T_PROTECTED)
                )
                && !$propertyExclusion
            ) {
                $this->insertVariablePropertyWarning($tokens, $key, self::PROTECTED_PROPERTIES_CONVENTION);
            }
            return $tokens[$key]->getContent();
        }
        return null;
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param string $convention
     */
    private function insertVariablePropertyWarning(Tokens $tokens, $key, $convention)
    {
        $this->insertComment(
            $tokens,
            $tokens->getNextTokenOfKind($key, [';']),
            $tokens[$key]->getContent(),
            $convention
        );
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param string $propertyName
     * @param string $convention
     */
    private function insertComment(Tokens $tokens, $insertIndex, $propertyName, $convention)
    {
        $comment = '// TODO: "' . $propertyName . '" - ' . $convention;
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt($insertIndex + 1, new Token([T_COMMENT, $comment]));
            $tokens->insertAt($insertIndex + 1, new Token([T_WHITESPACE, ' ']));
        }
    }
}
