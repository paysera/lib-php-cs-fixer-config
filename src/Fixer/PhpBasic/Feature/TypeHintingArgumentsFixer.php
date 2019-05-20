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
    private $unavailableTypeHints;
    private $inheritanceHelper;

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

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            If argument is optional, we provide default value for it.
            If optional argument is object, we type hint it with required class and add default to null.
            
            If argument is not optional, but just nullable, we can type hint it with default value null,
            but when using, we pass null explicitly.
            
            Risky, because of type hint copy from docBlock.
            ',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            /**
                             * @param ValueClass $value
                             */
                            public function setValue($value)
                            {
                         
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_type_hinting_arguments';
    }

    public function isRisky()
    {
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
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

    /**
     * @param int $functionTokenIndex
     * @param Tokens $tokens
     * @return bool
     */
    private function methodImplementedFromInterface($functionTokenIndex, Tokens $tokens)
    {
        $method = $tokens[$tokens->getNextNonWhitespace($functionTokenIndex)];
        return $this->inheritanceHelper->isMethodFromInterface($method->getContent(), $tokens);
    }

    /**
     * @param int $functionTokenIndex
     * @param Tokens $tokens
     * @return bool
     */
    private function methodCallsInheritedProperty($functionTokenIndex, Tokens $tokens)
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

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param int $parenthesesStartIndex
     * @param int $parenthesesEndIndex
     */
    private function validateObjectArguments(
        Tokens $tokens,
        $docBlockIndex,
        $parenthesesStartIndex,
        $parenthesesEndIndex
    ) {
        $currentParenthesesEndIndex = $parenthesesEndIndex;
        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
        for ($i = $parenthesesEndIndex; $i > $parenthesesStartIndex ; $i--) {
            if (!$tokens[$i]->isGivenKind(T_VARIABLE)) {
                continue;
            }
            $previousTokenIndex = $tokens->getPrevMeaningfulToken($i);
            if (!$tokens[$previousTokenIndex]->isGivenKind(T_STRING)) {
                /** @var Annotation $annotation */
                foreach ($docBlock->getAnnotationsOfType('param') as $annotation) {
                    $variable = $tokens[$i]->getContent();
                    if (
                        !preg_match(
                            '#^[^$]+@param\s([^$].*?[^\s])\s\\' . $variable . '#m',
                            $annotation->getContent()
                        )
                        || preg_match(
                            '#^[^$]+@param\s(.*?\[\])\s\\' . $variable . '#m',
                            $annotation->getContent()
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
                        $tokens->insertAt($i, new Token([T_STRING, $argumentType]));
                        $tokens->insertAt($i + 1, new Token([T_WHITESPACE, ' ']));
                        $currentParenthesesEndIndex += 2;
                    }

                    if ($nullFound) {
                        /** @var Token[] $variables */
                        $variables = (clone $tokens)->findGivenKind(
                            T_VARIABLE,
                            $parenthesesStartIndex,
                            $currentParenthesesEndIndex
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
                            $tokens->insertAt(++$variablePosition, new Token([T_WHITESPACE, ' ']));
                            $tokens->insertAt(++$variablePosition, new Token('='));
                            $tokens->insertAt(++$variablePosition, new Token([T_WHITESPACE, ' ']));
                            $tokens->insertAt(++$variablePosition, new Token([T_STRING, 'null']));
                        }
                    }
                    break;
                }
            }
        }
    }
}
