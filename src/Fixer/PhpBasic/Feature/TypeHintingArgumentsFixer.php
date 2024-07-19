<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Util\InheritanceHelper;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class TypeHintingArgumentsFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * Array of repetitive type hints
     *
     * @var array
     */
    private array $unavailableTypeHints;
    private InheritanceHelper $inheritanceHelper;

    public function __construct()
    {
        parent::__construct();

        $this->inheritanceHelper = new InheritanceHelper();
        $this->unavailableTypeHints = [
            'array',
            'void',
            'self',
            '$this',
            'mixed',
            'callable',
            'bool',
            'boolean',
            'float',
            'int',
            'integer',
            'string',
            'resource',
        ];
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<'TEXT'
If argument is optional, we provide default value for it.
If optional argument is object, we type hint it with required class and add default to null.

If argument is not optional, but just nullable, we can type hint it with default value null,
but when using, we pass null explicitly.

Risky, because of type hint copy from docBlock.
TEXT
            ,
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    /**
     * @param ValueClass $value
     */
    public function setValue($value)
    {
 
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
        return 'Paysera/php_basic_feature_type_hinting_arguments';
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $tokens->getPrevNonWhitespace($functionTokenIndex);
            if (
                $token->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
                && $tokens[$visibilityTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            ) {
                $index = $tokens->getPrevNonWhitespace($visibilityTokenIndex);
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $docBlockIndex = null;
                if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $index;
                } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $tokens->getPrevNonWhitespace($index);
                }

                if (
                    $docBlockIndex !== null
                    && !$this->methodCallsInheritedProperty($functionTokenIndex, $tokens)
                    && !$this->methodImplementedFromInterface($functionTokenIndex, $tokens)
                ) {
                    $this->validateObjectArguments($tokens, $docBlockIndex, $key + 1, $parenthesesEndIndex);
                }
            }
        }
    }

    private function methodImplementedFromInterface(int $functionTokenIndex, Tokens $tokens): bool
    {
        $method = $tokens[$tokens->getNextNonWhitespace($functionTokenIndex)];
        return $this->inheritanceHelper->isMethodFromInterface($method->getContent(), $tokens);
    }

    private function methodCallsInheritedProperty(int $functionTokenIndex, Tokens $tokens): bool
    {
        $objectOperators = $tokens->findGivenKind(T_OBJECT_OPERATOR, $functionTokenIndex);
        if (count($objectOperators) === null) {
            return false;
        }
        foreach ($objectOperators as $key => $objectOperator) {
            $varIndex = $tokens->getPrevNonWhitespace($key);
            if ($varIndex !== null && $tokens[$varIndex]->getContent() === '$this') {
                $property = $tokens[$tokens->getNextNonWhitespace($varIndex + 1)];
                if ($this->inheritanceHelper->isPropertyInherited($property->getContent(), $tokens)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function validateObjectArguments(
        Tokens $tokens,
        int $docBlockIndex,
        int $parenthesesStartIndex,
        int $parenthesesEndIndex
    ) {
        $currentParenthesesEndIndex = $parenthesesEndIndex;
        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
        for ($i = $parenthesesEndIndex; $i > $parenthesesStartIndex; $i--) {
            if (!$tokens[$i]->isGivenKind(T_VARIABLE)) {
                continue;
            }
            $previousTokenIndex = $tokens->getPrevMeaningfulToken($i);
            if (!$tokens[$previousTokenIndex]->isGivenKind(T_STRING)) {
                foreach ($docBlock->getAnnotationsOfType('param') as $annotation) {
                    $variable = $tokens[$i]->getContent();
                    if (
                        !preg_match(
                            '#^[^$]+@param\s([^$].*?[^\s])\s\\' . $variable . '#m',
                            $annotation->getContent(),
                        )
                        || preg_match(
                            '#^[^$]+@param\s(.*?\[\])\s\\' . $variable . '#m',
                            $annotation->getContent(),
                        )
                    ) {
                        continue;
                    }

                    $argumentTypes = $annotation->getTypes();
                    $argumentTypeCount = count($argumentTypes);
                    $nullFound = in_array('null', $argumentTypes, true);
                    if (
                        !array_intersect($argumentTypes, $this->unavailableTypeHints)
                        && (($argumentTypeCount === 2 && $nullFound) || ($argumentTypeCount === 1) && !$nullFound)
                    ) {
                        $argumentType = trim(implode('', array_diff($argumentTypes, ['null'])));
                        $tokens->insertSlices([
                            $i => [
                                new Token([T_STRING, $argumentType]),
                                new Token([T_WHITESPACE, ' ']),
                            ]
                        ]);
                        $currentParenthesesEndIndex += 2;
                    }

                    if ($nullFound) {
                        /** @var Token[] $variables */
                        $variables = (clone $tokens)->findGivenKind(
                            T_VARIABLE,
                            $parenthesesStartIndex,
                            $currentParenthesesEndIndex,
                        );
                        $variablePosition = null;
                        foreach ($variables as $key => $variableToken) {
                            $expectedEqual = $tokens->getNextMeaningfulToken($key);
                            $expectedNull = $tokens->getNextMeaningfulToken($expectedEqual);
                            if (
                                $tokens[$expectedEqual]->getContent() === '='
                                && $tokens[$expectedNull]->getContent() === 'null'
                            ) {
                                continue;
                            }

                            if ($variableToken->getContent() === $variable) {
                                $variablePosition = $key;
                                break;
                            }
                        }

                        if ($variablePosition !== null) {
                            $tokens->insertSlices([
                                ++$variablePosition => [
                                    new Token([T_WHITESPACE, ' ']),
                                    new Token('='),
                                    new Token([T_WHITESPACE, ' ']),
                                    new Token([T_STRING, 'null']),
                                ]
                            ]);
                        }
                    }
                    break;
                }
            }
        }
    }
}
