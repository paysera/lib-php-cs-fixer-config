<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class SplittingInSeveralLinesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public function getDefinition()
    {
        return new FixerDefinition(
            'Checks if &&, || etc. comes in the beginning of the line, not the end',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        public function sampleFunction()
                        {
                            $a = 1;
                            $b = 2;
                            $c = 3;
                            $d = 4;
                            
                            if ($a === 1) {
                                if ($b === 2) {
                                    in_array($a, [1,
                                        2, 3, 4,5,  ]);
                                }
                            }
                            
                            in_array($a, [1,
                                2, 3, 4,5  ], true);

                            return ((
                                $a
                                &&
                                $b
                            ) ||
                                (
                                $c
                                &&
                                $d
                            ));
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_splitting_in_several_lines';
    }

    public function getPriority()
    {
        // Should run before TrailingCommaInMultilineArrayFixer
        return 1;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_BOOLEAN_AND, T_BOOLEAN_OR, T_STRING]);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind([T_BOOLEAN_AND, T_BOOLEAN_OR])) {
                if ($tokens[$key + 1]->isWhitespace() && strpos($tokens[$key + 1]->getContent(), "\n") !== false) {
                    $indent = $tokens[$key + 1]->getContent();
                    $tokens[$key + 1]->setContent(' ');
                }

                if (isset($indent)
                    && $tokens[$key - 1]->isWhitespace()
                    && strpos($tokens[$key - 1]->getContent(), "\n") === false
                ) {
                    $tokens[$key - 1]->setContent($indent);
                }
            }

            if ($token->equals('(') && $tokens[$key - 1]->isGivenKind([T_STRING, T_ARRAY])) {
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key);

                $whitespaceToken = $this->checkForArgumentSplits($tokens, $key, $parenthesesEndIndex);
                if ($whitespaceToken !== null) {
                    $this->fixArgumentPlacements(
                        $tokens,
                        $whitespaceToken->getContent(),
                        $key + 1,
                        $parenthesesEndIndex - 1
                    );
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     * @param int $endIndex
     * @return Token|null
     */
    private function checkForArgumentSplits(Tokens $tokens, $startIndex, $endIndex)
    {
        for ($i = $startIndex; $i < $endIndex; ++$i) {
            // Skipping this function if argument splits belongs to another function
            if ($tokens[$i + 1]->equals('(') && $tokens[$i]->isGivenKind([T_STRING, T_ARRAY, T_FUNCTION])) {
                return null;
            }

            if ($tokens[$i]->isWhitespace() && strpos($tokens[$i]->getContent(), "\n") !== false) {
                // Takes first newline indent
                return $tokens[$i];
            }
        }
        return null;
    }

    /**
     * @param Tokens $tokens
     * @param string $whitespaceToken
     * @param int $startIndex
     * @param int $parenthesesEndIndex
     */
    private function fixArgumentPlacements(Tokens $tokens, $whitespaceToken, $startIndex, $parenthesesEndIndex)
    {
        $indent = $whitespaceToken;
        $endIndex = $parenthesesEndIndex;

        // Checking first argument if it's not an array square brace
        if ($tokens[$startIndex]->getContent() !== '[' && strpos($tokens[$startIndex]->getContent(), "\n") === false) {
            $tokens->insertAt($startIndex, new Token([T_WHITESPACE, $indent]));
            $endIndex++;
        }

        for ($i = $startIndex; $i < $endIndex; ++$i) {
            // Skipping if another function is found
            if ($tokens[$i]->equals('(')) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $i);
            }

            // Skip index type arrays: $var['something'] or $var['some']['thing']  or $var[$i]
            // And conditional elements (?, :): true ? [T_IS_IDENTICAL, '==='] : [T_IS_NOT_IDENTICAL, '!==']
            $blockType = Tokens::detectBlockType($tokens[$i]);
            if ($blockType['type'] === Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE && $blockType['isStart']) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $i);
            } elseif ($blockType['type'] === Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE && $blockType['isStart']) {
                $endSquareBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $i);
                $conditionTokenIndex = $tokens->getPrevMeaningfulToken($i);
                if ($this->isSingleArgumentArray($tokens, $i, $endSquareBraceIndex)
                    || $tokens[$conditionTokenIndex]->equalsAny(['?', ':'])
                ) {
                    $i = $endSquareBraceIndex;
                }
            }

            // For first square opening brace doubling indent
            if (($tokens[$i]->getContent() === '[' || $tokens[$i]->equals('('))
                && $tokens[$i - 1]->isGivenKind([T_WHITESPACE, ',', T_STRING])
            ) {
                $openSquareTokenIndex = $i;
                $indent .= $this->whitespacesConfig->getIndent();
                $endIndex = $this->insertWhitespace($tokens, $i + 1, $endIndex, $indent);
            }

            $nextTokenSquareBraceIndex = $tokens->getNextMeaningfulToken($i);
            if ($tokens[$i]->equals(',') && $tokens[$nextTokenSquareBraceIndex]->getContent() !== ']'
                && !$tokens[$tokens->getNextNonWhitespace($i)]->isGivenKind([T_COMMENT, T_DOC_COMMENT])
            ) {
                $endIndex = $this->insertWhitespace($tokens, $i + 1, $endIndex, $indent);
            }

            // Check if it's array, not argument in array
            if (($tokens[$i + 1]->getContent() === ']' || $tokens[$i + 1]->equals(')'))
                && isset($openSquareTokenIndex)
                && $tokens[$openSquareTokenIndex - 1]->isGivenKind([T_WHITESPACE, ',', T_STRING])
            ) {
                // For last closing brackets overriding indent
                if (strpos($tokens[$openSquareTokenIndex - 1]->getContent(), "\n") === false) {
                    // Removing 4 spaces if square open brace had no newline before it
                    $indent = preg_replace(
                        '/' . preg_quote($this->whitespacesConfig->getIndent(), '/') . '/',
                        '',
                        $indent,
                        1
                    );
                } else {
                    // Overriding to original indent if there was newline before square open brace
                    $indent = $whitespaceToken;
                }
                if ($tokens[$i]->isWhitespace()) {
                    $tokens[$i]->setContent($indent);
                } else {
                    $tokens->insertAt($i + 1, new Token([T_WHITESPACE, $indent]));
                    $endIndex++;
                }
            }
        }
        // Removing additional indent which was added earlier
        $indent = preg_replace('/' . preg_quote($this->whitespacesConfig->getIndent(), '/') . '/', '', $indent, 1);

        if ($tokens[$endIndex]->isWhitespace()) {
            $tokens[$endIndex]->setContent($indent);
            // Don't insert newline if square open brace was near start parentheses: ([
        } elseif (!$tokens[$endIndex]->isWhitespace()
            && ($tokens[$endIndex]->getContent() !== ']'
                || !($tokens[$startIndex]->getContent() === '[' && $tokens[$startIndex - 1]->equals('(')))
        ) {
            $tokens->insertAt($endIndex + 1, new Token([T_WHITESPACE, $indent]));
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     * @param int $endIndex
     * @return bool
     */
    private function isSingleArgumentArray(Tokens $tokens, $startIndex, $endIndex)
    {
        for ($i = $startIndex + 1; $i < $endIndex; $i++) {
            // Skipping all parentheses
            if ($tokens[$i]->equals('(')) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $i);
            }
            // Skipping all square braces
            if (Tokens::detectBlockType($tokens[$i])['type'] === Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ARRAY_SQUARE_BRACE, $i);
            } elseif (Tokens::detectBlockType($tokens[$i])['type'] === Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $i);
            }

            // If comma found and it's not the last - array has multiple arguments
            if ($tokens[$i]->equals(',') && $i + 1 !== $endIndex) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param int $endIndex
     * @param string $indent
     * @return int
     */
    private function insertWhitespace(Tokens $tokens, $insertIndex, $endIndex, $indent)
    {
        if ($tokens[$insertIndex]->isWhitespace()) {
            $tokens[$insertIndex]->setContent($indent);
        } else {
            $tokens->insertAt($insertIndex, new Token([T_WHITESPACE, $indent]));
            $endIndex++;
        }
        return $endIndex;
    }
}
