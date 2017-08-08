<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class StrictComparisonOperatorsFixer extends AbstractFixer
{
    const IN_ARRAY_FUNCTION = 'in_array';
    const TRUE = 'true';
    const FALSE = 'false';

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We use === (!==) instead of == (!=) everywhere,
            except cases where we explicitly want to check ignoring the type.
            Same applies for in_array - we always pass third argument as true for strict checking.
            We convert or validate types when data is entering the system - on normalizers, forms or controllers.
            
            Risky, if ignoring types was intentional
            ',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            private function sampleFunction()
                            {
                                if ($a == $b) {
                                    return in_array($this->someFunction(), [0, 1, 2], $this->someStrictFunction());
                                }
                                
                                if ($a != $b) {
                                    return in_array($b, $ab);
                                }
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_strict_comparison_operators';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_IS_EQUAL, T_IS_NOT_EQUAL, T_STRING]);
    }

    public function isRisky()
    {
        return true;
    }

    public function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_IS_EQUAL)) {
                $tokens->overrideAt($key, [T_IS_IDENTICAL, '===']);
            } elseif ($token->isGivenKind(T_IS_NOT_EQUAL)) {
                $tokens->overrideAt($key, [T_IS_NOT_IDENTICAL, '!==']);
            }

            if ($token->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
                && $token->getContent() === self::IN_ARRAY_FUNCTION
            ) {
                $blockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $this->fixStrictComparison($tokens, $key + 2, $blockEndIndex - 1);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     * @param int $endIndex
     */
    private function fixStrictComparison(Tokens $tokens, $startIndex, $endIndex)
    {
        $count = 0;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            $blockType = Tokens::detectBlockType($tokens[$i]);
            if ($blockType['type'] === Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE && $blockType['isStart']) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $i);
            }

            if ($blockType['type'] === Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE && $blockType['isStart']) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $i);
            }

            if ($tokens[$i]->equals('(')) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $i);
            }

            if ($tokens[$i]->equals(',')) {
                $count++;
                if ($count === 2) {
                    $thirdArgumentIndex = $tokens->getNextMeaningfulToken($i);
                    if (strtolower($tokens[$thirdArgumentIndex]->getContent()) === self::TRUE) {
                        break;
                    }

                    if (strtolower($tokens[$thirdArgumentIndex]->getContent()) === self::FALSE) {
                        $tokens[$thirdArgumentIndex]->setContent(self::TRUE);
                        break;
                    }
                }
            }
        }

        if ($count === 1) {
            $tokens->insertAt($endIndex + 1, new Token([T_STRING, self::TRUE]));
            $tokens->insertAt($endIndex + 1, new Token([T_WHITESPACE, ' ']));
            $tokens->insertAt($endIndex + 1, new Token(','));
        }
    }
}
