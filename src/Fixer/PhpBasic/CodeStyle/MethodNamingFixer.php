<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class MethodNamingFixer extends AbstractFixer
{
    const BOOL_FUNCTION = 'PhpBasic convention 2.5.5: We use prefix - has, is, can for bool functions';
    const ENTITY_FUNCTIONS = 'PhpBasic convention 2.5.5: Invalid entity function name';
    const ENTITY = 'Entity';
    const TRUE = 'true';
    const FALSE = 'false';

    /**
     * @var array
     */
    private $validBoolFunctionPrefixes = [
        'is',
        'are',
        'has',
        'can',
        'apply',
        'matches',
        'check',
    ];

    /**
     * @var array
     */
    private $validEntityFunctionPrefixes = [
        'is',
        'are',
        'has',
        'can',
        'on',
        'get',
        'set',
        'add',
        'create',
        'remove',
        'clear',
        'mark',
        '__',
    ];

    public function getDefinition()
    {
        return new FixerDefinition('
            We use verbs for methods that perform action and/or return something,
            questions only for methods which return boolean.
            
            Questions start with has, is, can - these cannot make any side-effect and always return boolean.
            
            For entities we use is* or are* for boolean getters, get* 
            for other getters, set* for setters, add* for adders, remove* for removers.
            
            We always make correct English phrase from method names,
            this is more important that naming method to \'is\' + propertyName.
            ',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        public function someInvalidName()
                        {
                            return false;
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_method_naming';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $classNamespace = null;
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $semicolonIndex = $tokens->getNextTokenOfKind($key, [';']);
                if ($tokens[$semicolonIndex - 1]->isGivenKind(T_STRING)) {
                    $classNamespace = $tokens[$semicolonIndex - 1]->getContent();
                }
            }

            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $tokens->getPrevNonWhitespace($functionTokenIndex);
            if ($token->isGivenKind(T_STRING) && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
                && $tokens[$visibilityTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            ) {
                $functionName = $tokens[$key]->getContent();
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);
                if (!$tokens[$curlyBraceStartIndex]->equals('{')) {
                    continue;
                }

                if ($classNamespace === self::ENTITY) {
                    $this->validateEntityFunctionName($tokens, $functionName, $curlyBraceStartIndex);
                    continue;
                }

                $index = $tokens->getPrevNonWhitespace($visibilityTokenIndex);
                $docBlockIndex = null;
                if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $index;
                } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $tokens->getPrevNonWhitespace($index);
                }

                $shouldReturnBool = preg_match(
                    '#^(?:' . implode('|', $this->validBoolFunctionPrefixes) . ')[A-Z]#',
                    $functionName
                );

                if ($docBlockIndex !== null) {
                    $returnsBool = preg_match('#@return\s.*(boolean|bool)#', $tokens[$docBlockIndex]->getContent());
                    if ($shouldReturnBool && !$returnsBool) {
                        $this->insertComment($tokens, $curlyBraceStartIndex, $functionName, self::BOOL_FUNCTION);
                    }
                } else {
                    $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
                    for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; ++$i) {
                        if ($tokens[$i]->isGivenKind(T_FUNCTION)) {
                            break;
                        }
                        $nextTokenValue = strtolower($tokens[$tokens->getNextMeaningfulToken($i)]->getContent());
                        if ($tokens[$i]->isGivenKind(T_RETURN)
                            && !in_array($nextTokenValue, [self::TRUE, self::FALSE], true)
                            && $shouldReturnBool
                        ) {
                            $this->insertComment($tokens, $curlyBraceStartIndex, $functionName, self::BOOL_FUNCTION);
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param string $functionName
     * @param int $insertIndex
     */
    private function validateEntityFunctionName(Tokens $tokens, $functionName, $insertIndex)
    {
        if (!preg_match('#^' . implode('|', $this->validEntityFunctionPrefixes) . '#', $functionName)) {
            $this->insertComment($tokens, $insertIndex, $functionName, self::ENTITY_FUNCTIONS);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param string $functionName
     * @param string $convention
     */
    private function insertComment(Tokens $tokens, $insertIndex, $functionName, $convention)
    {
        $comment = '// TODO: "' . $functionName . '" - ' . $convention;
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt($insertIndex + 1, new Token([T_COMMENT, $comment]));
            $tokens->insertAt($insertIndex + 1, new Token([T_WHITESPACE, ' ']));
        }
    }
}
