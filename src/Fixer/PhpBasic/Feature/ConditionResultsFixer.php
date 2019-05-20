<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ConditionResultsFixer extends AbstractFixer
{
    const TRUE = 'true';
    const FALSE = 'false';

    /**
     * @var array
     */
    private $strictValues;

    public function __construct()
    {
        parent::__construct();
        $this->strictValues = [
            'true',
            'false',
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            'If condition result is boolean, we do not use condition at all.',
            [
                new CodeSample('
                <?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = $d && $e ? false : true;
                        
                        if ($a && $b) {
                            return true;
                        }
                        
                        return false;
                    }
                }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_condition_results';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_IF, T_RETURN, T_VARIABLE]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            // Validate long if statement
            if ($token->isGivenKind(T_IF)) {
                $this->validateLongIfStatement($tokens, $key, false);
            }

            // Validate return with short if statement
            if ($token->isGivenKind(T_RETURN)) {
                $this->validateShortIfStatement($tokens, $key, false);
            }

            // Validate short if statement
            $nextTokenIndex = $tokens->getNextMeaningfulToken($key);
            if (!$token->isGivenKind(T_VARIABLE) && isset($nextTokenIndex) && $tokens[$nextTokenIndex]->equals('=')) {
                $this->validateShortIfStatement($tokens, $nextTokenIndex, true);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param bool $assignCondition
     */
    private function validateShortIfStatement(Tokens $tokens, $key, $assignCondition)
    {
        if (!$tokens[$key + 1]->isWhitespace()) {
            return;
        }
        $index = $key + 2;
        while (!$tokens[$index]->equals(';')) {
            if (!$tokens[$index + 1]->equals('?') && !$tokens[$index]->equals('?')) {
                $ifStatementConditionTokens[] = $tokens[$index];
            }

            if ($tokens[$index]->equals('?')) {
                $firstBoolIndex = $tokens->getNextMeaningfulToken($index);
                if (!$this->isStrictValue($tokens[$firstBoolIndex])) {
                    return;
                }

                $colonIndex = $tokens->getNextMeaningfulToken($firstBoolIndex);
                if (!$tokens[$colonIndex]->equals(':')) {
                    return;
                }

                $secondBoolIndex = $tokens->getNextMeaningfulToken($colonIndex);
                if (!$this->isStrictValue($tokens[$secondBoolIndex])) {
                    return;
                }

                $semicolonIndex = $tokens->getNextMeaningfulToken($secondBoolIndex);
                if (!$tokens[$semicolonIndex]->equals(';')) {
                    return;
                }

                if (isset($ifStatementConditionTokens)) {
                    $this->fixIfStatement(
                        $tokens,
                        $key,
                        $ifStatementConditionTokens,
                        $tokens[$firstBoolIndex]->getContent(),
                        $semicolonIndex,
                        $assignCondition
                    );
                }
            }
            $index++;
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param bool $assignCondition
     */
    private function validateLongIfStatement(Tokens $tokens, $key, $assignCondition)
    {
        $parenthesesStartIndex = $tokens->getNextMeaningfulToken($key);
        if (!$tokens[$parenthesesStartIndex]->equals('(')) {
            return;
        }

        $parenthesesEndIndex = $tokens->findBlockEnd(
            Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
            $parenthesesStartIndex
        );

        $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);
        if (!$tokens[$curlyBraceStartIndex]->equals('{')) {
            return;
        }

        $firstReturnStatement = $this->checkReturnBoolCondition($tokens, $curlyBraceStartIndex);
        if ($firstReturnStatement === null) {
            return;
        }

        $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
        if ($tokens->getNextMeaningfulToken($firstReturnStatement['SemicolonIndex']) !== $curlyBraceEndIndex) {
            return;
        }

        $ifStatementConditionTokens = [];
        for ($i = $parenthesesStartIndex + 1; $i < $parenthesesEndIndex; $i++) {
            $ifStatementConditionTokens[] = $tokens[$i];
        }

        $elseIndex = $tokens->getNextMeaningfulToken($curlyBraceEndIndex);

        $secondReturnStatement = $this->checkReturnBoolCondition($tokens, $curlyBraceEndIndex);
        if ($secondReturnStatement !== null) {
            $this->fixIfStatement(
                $tokens,
                $key,
                $ifStatementConditionTokens,
                $firstReturnStatement['BoolCondition'],
                $secondReturnStatement['SemicolonIndex'],
                $assignCondition
            );
        } elseif ($tokens[$elseIndex]->isGivenKind(T_ELSE)) {
            $elseCurlyBraceStartIndex = $tokens->getNextMeaningfulToken($elseIndex);

            if (!$tokens[$elseCurlyBraceStartIndex]->equals('{')) {
                return;
            }

            $secondReturnStatement = $this->checkReturnBoolCondition($tokens, $elseCurlyBraceStartIndex);
            if ($secondReturnStatement === null) {
                return;
            }

            $elseCurlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $elseCurlyBraceStartIndex);
            if ($tokens->getNextMeaningfulToken($secondReturnStatement['SemicolonIndex']) !== $elseCurlyBraceEndIndex) {
                return;
            }

            $this->fixIfStatement(
                $tokens,
                $key,
                $ifStatementConditionTokens,
                $firstReturnStatement['BoolCondition'],
                $elseCurlyBraceEndIndex,
                $assignCondition
            );
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param Token[] $ifStatementConditionTokens
     * @param string $returnCondition
     * @param int $endIndex
     * @param bool $assignCondition
     */
    private function fixIfStatement(
        Tokens $tokens,
        $key,
        $ifStatementConditionTokens,
        $returnCondition,
        $endIndex,
        $assignCondition
    ) {
        $insertionIndex = $endIndex;

        if ($assignCondition) {
            $tokens->insertAt(++$insertionIndex, new Token('='));
        } else {
            $tokens->insertAt(++$insertionIndex, new Token([T_RETURN, 'return']));
        }

        $tokens->insertAt(++$insertionIndex, new Token([T_WHITESPACE, ' ']));
        if ($returnCondition === self::TRUE) {
            $tokens->insertAt(++$insertionIndex, new Token([T_WHITESPACE, ' ']));
            $overrideIndex = $insertionIndex;
        } else {
            $tokens->insertAt(++$insertionIndex, new Token('!'));
            $tokens->insertAt(++$insertionIndex, new Token('('));
            $tokens->insertAt(++$insertionIndex, new Token([T_WHITESPACE, ' ']));
            $overrideIndex = $insertionIndex;
            $tokens->insertAt(++$insertionIndex, new Token(')'));
        }
        $tokens->insertAt(++$insertionIndex, new Token(';'));

        $tokens->overrideRange(
            $overrideIndex,
            $overrideIndex,
            $ifStatementConditionTokens
        );

        $tokens->clearRange($key, $endIndex);
    }

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     * @return array
     */
    private function checkReturnBoolCondition(Tokens $tokens, $startIndex)
    {
        $returnIndex = $tokens->getNextMeaningfulToken($startIndex);
        if (!$tokens[$returnIndex]->isGivenKind(T_RETURN)) {
            return null;
        }

        $boolIndex = $tokens->getNextMeaningfulToken($returnIndex);
        if (!$this->isStrictValue($tokens[$boolIndex])) {
            return null;
        }

        $semicolonIndex = $tokens->getNextMeaningfulToken($boolIndex);
        if (!$tokens[$semicolonIndex]->equals(';')) {
            return null;
        }

        $returnStatement['BoolCondition'] = $tokens[$boolIndex]->getContent();
        $returnStatement['SemicolonIndex'] = $semicolonIndex;
        return $returnStatement;
    }

    private function isStrictValue(Token $token)
    {
        return in_array($token->getContent(), $this->strictValues, true);
    }
}
