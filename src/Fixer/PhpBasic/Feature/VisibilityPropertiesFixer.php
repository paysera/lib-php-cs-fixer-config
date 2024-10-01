<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionClass;
use SplFileInfo;
use Exception;

final class VisibilityPropertiesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    use ConfigurableFixerTrait {
        configure as public configureConfigurableFixerTrait;
    }

    public const DYNAMIC_PROPERTIES_CONVENTION = 'PhpBasic convention 3.14.1: We avoid dynamic properties';
    public const PUBLIC_PROPERTIES_CONVENTION = 'PhpBasic convention 3.14.1: We don’t use public properties';
    public const PROTECTED_PROPERTIES_CONVENTION = 'PhpBasic convention 3.14.2: We prefer use private over protected properties';
    public const VISIBILITY_CONVENTION = 'PhpBasic convention 3.14: We must define as properties';
    public const THIS = '$this';

    private array $excludedParents;

    public function __construct()
    {
        parent::__construct();

        $this->excludedParents = [
            'Constraint',
        ];
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<'TEXT'
We don’t use public and dynamic properties. All used properties must be defined.

We prefer private over protected as it constraints the scope - it’s easier to refactor,
find usages, plan possible changes in code. Also IDE can warn about unused methods or properties.

We use protected when we intend some property or method to be overwritten if necessary.

TEXT,
            [
                new CodeSample(
                    <<<'PHP'
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
        $foo->createProperty('hello', 'something');
    }
    
    public function createProperty($name, $value){
        $this->{$name} = $value;
    }
}

PHP,
                ),
            ],
            null,
            'Paysera recommendation.',
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_feature_visibility_properties';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function getPriority(): int
    {
        // Should run before `OrderedClassElementsFixer` and after `NamespacesAndUseStatementsFixer`
        return 60;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_PUBLIC, T_PRIVATE, T_PROTECTED, T_VARIABLE, T_STRING]);
    }

    public function configure(array $configuration = null): void
    {
        $this->configureConfigurableFixerTrait($configuration);

        if ($this->configuration['excluded_parents'] === true) {
            return;
        }
        if (isset($this->configuration['excluded_parents'])) {
            $this->excludedParents = $this->configuration['excluded_parents'];
        }
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolver
    {
        return
            new FixerConfigurationResolver(
                [
                    (new FixerOptionBuilder(
                        'excluded_parents',
                        'Allows to set Parent class names where in children Classes it is allowed to use public or protected properties',
                    ))
                        ->setAllowedTypes(['array', 'bool'])
                        ->getOption(),
                ],
            );
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
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
                            true,
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
                        $key,
                        $tokens[$key]->getContent(),
                        self::DYNAMIC_PROPERTIES_CONVENTION,
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
                    $classNamespace,
                );
            }
        }
    }

    private function validateNotDefinedProperties(
        Tokens $tokens,
        int $key,
        array $propertyVariables,
        string $propertyName,
        string $classNamespace
    ): void {
        $variable = '$' . $propertyName;
        if (
            !in_array($variable, $propertyVariables, true)
            && !$this->isPropertyInAnotherClass($propertyName, $classNamespace)
        ) {
            $this->insertVariablePropertyWarning($tokens, $key, self::VISIBILITY_CONVENTION);
        }
    }

    private function isPropertyInAnotherClass(string $variableName, ?string $classNamespace): bool
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

    private function getPropertyVariable(Tokens $tokens, int $key, bool $propertyExclusion): ?string
    {
        /** @var array<Token> $previousTokens */
        $index = $key;

        for ($i = 0; $i < 4; $i++) {
            $previousTokenIndex = $tokens->getPrevMeaningfulToken($index);
            $previousTokens[] = $tokens[$previousTokenIndex];
            $index = $previousTokenIndex;
        }

        $visibilityToken = $this->getVisibilityToken($previousTokens);

        if ($visibilityToken !== null) {
            if (
                $visibilityToken->isGivenKind([T_PUBLIC, 10028])
                && !$propertyExclusion
            ) {
                $this->insertVariablePropertyWarning($tokens, $key, self::PUBLIC_PROPERTIES_CONVENTION);
            }

            if (
                $visibilityToken->isGivenKind([T_PROTECTED, 10029])
                && !$propertyExclusion
            ) {
                $this->insertVariablePropertyWarning($tokens, $key, self::PROTECTED_PROPERTIES_CONVENTION);
            }

            return $tokens[$key]->getContent();
        }

        return null;
    }

    private function insertVariablePropertyWarning(Tokens $tokens, int $key, string $convention): void
    {
        $this->insertComment(
            $tokens,
            $key,
            $tokens[$key]->getContent(),
            $convention,
        );
    }

    private function insertComment(Tokens $tokens, int $insertIndex, string $propertyName, string $convention): void
    {
        $comment = '// TODO: "' . $propertyName . '" - ' . $convention;

        do {
            $token = $tokens[$insertIndex++];
        } while (!substr_count($token->getContent(), "\n"));

        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex - 3)]->isGivenKind(T_COMMENT)) {
            $tokens->insertSlices([
                $insertIndex - 1 => [
                    new Token([T_WHITESPACE, ' ']),
                    new Token([T_COMMENT, $comment]),
                ],
            ]);
        }
    }

    /**
     * @param array<Token> $previousTokens
     * @return Token|null
     */
    private function getVisibilityToken(array $previousTokens): ?Token
    {
        $visibilityToken = null;

        foreach ($previousTokens as $previousToken) {
            if ($previousToken->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE, 10028, 10029, 10030])) {
                $visibilityToken = $previousToken;
                break;
            }

            if ($previousToken->isGivenKind(T_FUNCTION)) {
                break;
            }
        }

        return $visibilityToken;
    }
}
