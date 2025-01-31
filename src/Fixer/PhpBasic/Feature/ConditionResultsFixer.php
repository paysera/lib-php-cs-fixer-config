<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ConditionResultsFixer extends AbstractFixer
{
    public const TRUE = 'true';
    public const FALSE = 'false';

    private array $strictValues;

    public function __construct()
    {
        parent::__construct();

        $this->strictValues = [
            'true',
            'false',
        ];
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'If condition result is boolean, we do not use condition at all.',
            [
                new CodeSample(
                    <<<'PHP'
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

PHP,
                ),
            ],
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_feature_condition_results';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_IF, T_RETURN, T_VARIABLE]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
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

    private function validateShortIfStatement(Tokens $tokens, int $key, bool $assignCondition)
    {
        if (!$tokens[$key + 1]->isWhitespace()) {
            return;
        }
        $index = $key + 2;
        while (!$tokens[$index]->equals(';')) {
            if (!isset($tokens[$index + 1])) {
                return;
            }

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
                        $assignCondition,
                    );
                }
            }
            $index++;
        }
    }

    private function validateLongIfStatement(Tokens $tokens, int $key, bool $assignCondition)
    {
        $parenthesesStartIndex = $tokens->getNextMeaningfulToken($key);
        if (!$tokens[$parenthesesStartIndex]->equals('(')) {
            return;
        }

        $parenthesesEndIndex = $tokens->findBlockEnd(
            Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
            $parenthesesStartIndex,
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
                $assignCondition,
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
                $assignCondition,
            );
        }
    }

    /**
     * @param Token[] $ifStatementConditionTokens
     * @param Tokens $tokens
     * @param int $key
     * @param string $returnCondition
     * @param int $endIndex
     * @param bool $assignCondition
     */
    private function fixIfStatement(
        Tokens $tokens,
        int $key,
        array $ifStatementConditionTokens,
        string $returnCondition,
        int $endIndex,
        bool $assignCondition
    ) {
        $insertionIndex = $endIndex;

        if ($assignCondition) {
            $tokens->insertSlices([++$insertionIndex => [new Token('=')]]);
        } else {
            $tokens->insertSlices([++$insertionIndex => [new Token([T_RETURN, 'return'])]]);
        }

        $tokens->insertSlices([++$insertionIndex => [new Token([T_WHITESPACE, ' '])]]);
        if ($returnCondition === self::TRUE) {
            $tokens->insertSlices([++$insertionIndex => [new Token([T_WHITESPACE, ' '])]]);
            $overrideIndex = $insertionIndex;
        } else {
            $tokens->insertSlices([
                ($insertionIndex + 1) => [
                    new Token('!'),
                    new Token('('),
                    new Token([T_WHITESPACE, ' ']),
                    new Token(')'),
                ],
            ]);
            $overrideIndex = $insertionIndex + 3;
            $insertionIndex += 4;
        }
        $tokens->insertSlices([++$insertionIndex => [new Token(';')]]);

        $tokens->overrideRange(
            $overrideIndex,
            $overrideIndex,
            $ifStatementConditionTokens,
        );

        $tokens->clearRange($key, $endIndex);
    }

    private function checkReturnBoolCondition(Tokens $tokens, int $startIndex): ?array
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

    private function isStrictValue(Token $token): bool
    {
        return in_array($token->getContent(), $this->strictValues, true);
    }
}
