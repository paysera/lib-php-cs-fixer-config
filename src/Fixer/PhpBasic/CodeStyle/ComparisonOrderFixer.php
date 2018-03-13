<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class ComparisonOrderFixer extends AbstractFixer
{
    /**
     * @var array
     */
    private $equalVariations = [
        T_IS_EQUAL,
        T_IS_IDENTICAL,
        T_IS_NOT_EQUAL,
        T_IS_NOT_IDENTICAL,
    ];

    public function getDefinition()
    {
        return new FixerDefinition('
            If we compare to static value (true, false, null, hard-coded integer or string),
            we put static value in the right of comparison operator.
            
            Risky, possible incompatibility.
            ',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        private function someOtherFunction()
                        {
                            return true;
                        }
                    
                        public function sampleFunction()
                        {
                            $a = 0;
                            $b = "string";
                            $d = false;
                            $f = null;
                            
                            if (0 >= $a) {
                                if ("string" === $b) {
                                    if (true !== $this->someOtherFunction()) {
                                        if (false === $d) {
                                            if (null !== $f) {
                                                return;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_comparison_order';
    }

    public function isRisky()
    {
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound($this->equalVariations);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind($this->equalVariations)) {
                continue;
            }
            $staticValueIndex = $tokens->getPrevNonWhitespace($key);
            $previousTokenIndex = $tokens->getPrevMeaningfulToken($staticValueIndex);

            // Does not handle cases when ! operator is used example: if (0 === !$a) and vice versa
            if (($tokens[$staticValueIndex]->isGivenKind([T_LNUMBER, T_CONSTANT_ENCAPSED_STRING])
                    || $tokens[$staticValueIndex]->isNativeConstant())
                && !$tokens[$staticValueIndex - 1]->equals('!')
                && ($tokens[$previousTokenIndex]->isGivenKind([T_BOOLEAN_AND, T_BOOLEAN_OR])
                    || $tokens[$previousTokenIndex]->equals('('))
                && !$tokens[$previousTokenIndex]->equals('.')
            ) {
                if ($tokens[$key + 1]->isWhitespace() && !$tokens[$key + 2]->equals('!')) {
                    // Copying static value to the right side
                    $tokens->insertAt($key + 1, new Token([T_WHITESPACE, ' ']));
                    $tokens->overrideAt($key + 2, $tokens[$staticValueIndex]);

                    // Switching argument to the static value's place
                    $argument = $this->findArgument($tokens, $key + 3);
                    if (count($argument) > 0) {
                        $tokens->overrideRange($staticValueIndex, $staticValueIndex, $argument);
                        $oldIndexStart = $key + count($argument) + 2;
                        $oldIndexEnd = $oldIndexStart + count($argument) - 1;
                        $tokens->clearRange($oldIndexStart, $oldIndexEnd);
                    }
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $argumentStartIndex
     * @return array
     */
    private function findArgument(Tokens $tokens, $argumentStartIndex)
    {
        $argumentEndIndex = $argumentStartIndex;

        while (!$tokens[$argumentEndIndex]->isWhitespace()
            && !$tokens[$argumentEndIndex + 1]->isGivenKind([
                T_BOOLEAN_AND,
                T_LOGICAL_AND,
                T_BOOLEAN_OR,
                T_LOGICAL_OR,
                T_LOGICAL_XOR,
            ])
        ) {
            $startMeaningful = $tokens->getNextMeaningfulToken($argumentStartIndex);
            if ($tokens[$startMeaningful]->equals('(')) {
                $argumentEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startMeaningful);
                return $this->getArgument($tokens, $argumentStartIndex, $argumentEndIndex);
            }

            $endMeaningful = $tokens->getNextMeaningfulToken($argumentEndIndex);
            if (
                $tokens[$endMeaningful]->equals(')')
                && $tokens[$tokens->getNextMeaningfulToken($endMeaningful)]->equals('{')
                || $tokens[$endMeaningful]->isWhitespace()
                || $tokens[$endMeaningful]->equals(';')
            ) {
                return $this->getArgument($tokens, $argumentStartIndex, $argumentEndIndex);
            }
            $argumentEndIndex++;
        }
        return $this->getArgument($tokens, $argumentStartIndex, $argumentEndIndex - 1);
    }

    /**
     * @param Tokens $tokens
     * @param int $argumentStartIndex
     * @param int $argumentEndIndex
     * @return array
     */
    private function getArgument(Tokens $tokens, $argumentStartIndex, $argumentEndIndex)
    {
        $argumentTokens = [];
        for ($i = $argumentStartIndex; $i <= $argumentEndIndex; $i++) {
            $argumentTokens[] = $tokens[$i];
        }
        return $argumentTokens;
    }
}
