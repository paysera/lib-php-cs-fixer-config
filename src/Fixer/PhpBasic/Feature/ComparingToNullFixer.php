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

final class ComparingToNullFixer extends AbstractFixer
{
    public const NON_OBJECT_TYPES = ['bool', 'int', 'float', 'string', 'array', 'callable', 'iterable'];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'When comparing to null, we always compare explicitly.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    private function sampleFunction(Something $something = null)
    {
        if ($something) {
    
        }
    }
}

PHP,
                ),
            ],
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_feature_comparing_to_null';
    }

    public function getPriority(): int
    {
        // Should run after `ComparisonOrderFixer`
        return -1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        // Collect all object variables
        $objectVariables = [];
        foreach ($tokens as $key => $token) {
            if (
                $token->isGivenKind(T_STRING)
                && !in_array($token->getContent(), self::NON_OBJECT_TYPES, true)
            ) {
                $objectVariableIndex = $tokens->getNextMeaningfulToken($key);
                if ($tokens[$objectVariableIndex]->isGivenKind(T_VARIABLE)) {
                    $objectVariables[] = $tokens[$objectVariableIndex]->getContent();
                }
            }
        }

        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind([T_IF, T_ELSEIF])) {
                $parenthesesStartIndex = $tokens->getNextMeaningfulToken($key);
                if ($tokens[$parenthesesStartIndex]->equals('(')) {
                    $this->validateLongIfExplicitCompare($tokens, $parenthesesStartIndex, $objectVariables);
                }
            } elseif (
                $token->isGivenKind(T_VARIABLE)
                && in_array($token->getContent(), $objectVariables, true)
                && $tokens[$tokens->getNextMeaningfulToken($key)]->equals('?')
            ) {
                $this->validateShortIfExplicitCompare($tokens, $key);
            }
        }
    }

    private function validateShortIfExplicitCompare(Tokens $tokens, int $objectVariableIndex)
    {
        $previousTokenIndex = $tokens->getPrevMeaningfulToken($objectVariableIndex);
        if ($tokens[$previousTokenIndex]->equals('!')) {
            $this->insertNullExplicitCompare($tokens, $objectVariableIndex, true);
            $tokens->clearRange($previousTokenIndex, $previousTokenIndex);
        } else {
            $this->insertNullExplicitCompare($tokens, $objectVariableIndex, false);
        }
    }

    private function validateLongIfExplicitCompare(Tokens $tokens, int $startIndex, array $objectVariables)
    {
        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);
        for ($i = $startIndex; $i < $endIndex; $i++) {
            if ($tokens[$i]->isGivenKind(T_VARIABLE) && in_array($tokens[$i]->getContent(), $objectVariables, true)) {
                $previousTokenIndex = $tokens->getPrevMeaningfulToken($i);
                $previousPreviousTokenIndex = $tokens->getPrevMeaningfulToken($previousTokenIndex);
                $nextTokenIndex = $tokens->getNextMeaningfulToken($i);
                if (
                    !$tokens[$nextTokenIndex]->isGivenKind([T_IS_IDENTICAL, T_IS_NOT_IDENTICAL])
                    && (
                        ($tokens[$nextTokenIndex]->equals(')') && $nextTokenIndex === $endIndex)
                        || $tokens[$nextTokenIndex]->isGivenKind([T_BOOLEAN_AND, T_BOOLEAN_OR])
                    )
                ) {
                    if (
                        $tokens[$previousTokenIndex]->equals('!')
                        && (
                            $tokens[$previousPreviousTokenIndex]->isGivenKind([T_BOOLEAN_AND, T_BOOLEAN_OR])
                            || (
                                $tokens[$previousPreviousTokenIndex]->equals('(')
                                && $previousPreviousTokenIndex === $startIndex
                            )
                        )
                    ) {
                        $this->insertNullExplicitCompare($tokens, $i, true);
                        $tokens->clearRange($previousTokenIndex, $previousTokenIndex);
                    } elseif (
                        $tokens[$previousTokenIndex]->isGivenKind([T_BOOLEAN_AND, T_BOOLEAN_OR])
                        || (
                            $tokens[$previousTokenIndex]->equals('(')
                            && $previousTokenIndex === $startIndex
                        )
                    ) {
                        $this->insertNullExplicitCompare($tokens, $i, false);
                    }
                }
            }
        }
    }

    private function insertNullExplicitCompare(Tokens $tokens, int $insertIndex, bool $identical)
    {
        $tokens->insertSlices([
            ++$insertIndex => [
                new Token([T_WHITESPACE, ' ']),
                new Token($identical ? [T_IS_IDENTICAL, '==='] : [T_IS_NOT_IDENTICAL, '!==']),
                new Token([T_WHITESPACE, ' ']),
                new Token([T_STRING, 'null']),
            ]
        ]);
    }
}
