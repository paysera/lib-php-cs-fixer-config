<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class ComparingToBooleanFixer extends AbstractFixer
{
    const TRUE = 'true';
    const FALSE = 'false';

    /**
     * @var array
     */
    private $strictValues = [
        'true',
        'false',
    ];

    public function getDefinition()
    {
        return new FixerDefinition('
            We do not use true/false keywords when checking variable which is already boolean.
            ',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            private function sampleFunction()
                            {
                                $valid = false;
                                if ($valid === false) {
                                    return $valid !== true;
                                }
                                
                                if ($valid === true) {
                                    return $valid === false;
                                }
                                
                                if ($valid === true) {
                                    return false !== $valid;
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
        return 'Paysera/php_basic_feature_comparing_to_boolean';
    }

    public function getPriority()
    {
        // Should run after `ComparisonOrderFixer`
        return -10;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_IS_IDENTICAL, T_IS_NOT_IDENTICAL]);
    }

    public function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $invalidVariables = [];

        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_VARIABLE)) {
                continue;
            }

            if (in_array($token->getContent(), $invalidVariables, true)) {
                continue;
            }

            $assignIndex = $tokens->getNextMeaningfulToken($key);
            $nextTokenIndex = $tokens->getNextMeaningfulToken($assignIndex);
            if ($tokens[$assignIndex]->equals('=') && !$tokens[$nextTokenIndex]->isNativeConstant()) {
                $invalidVariables[] = $token->getContent();
                continue;
            }

            $identicalIndex = $tokens->getNextMeaningfulToken($key);
            if (!$tokens[$identicalIndex]->isGivenKind([T_IS_IDENTICAL, T_IS_NOT_IDENTICAL])) {
                continue;
            }

            $boolIndex = $tokens->getNextMeaningfulToken($identicalIndex);
            if (!$this->isStrictValue($tokens[$boolIndex])) {
                continue;
            }

            $endConditionIndex = $tokens->getNextMeaningfulToken($boolIndex);
            if ($tokens[$endConditionIndex]->equalsAny([[')'], [';'], T_BOOLEAN_AND, T_BOOLEAN_OR])) {
                continue;
            }

            $this->fixBoolComparison(
                $tokens,
                $tokens[$boolIndex]->getContent(),
                $tokens[$identicalIndex]->getContent(),
                $key,
                $identicalIndex - 1,
                $boolIndex
            );
        }
    }

    /**
     * @param Tokens $tokens
     * @param string $boolCondition
     * @param string $comparisonOperator
     * @param int $insertIndex
     * @param int $clearStart
     * @param int $clearEnd
     */
    private function fixBoolComparison(
        Tokens $tokens,
        $boolCondition,
        $comparisonOperator,
        $insertIndex,
        $clearStart,
        $clearEnd
    ) {
        $tokens->clearRange($clearStart, $clearEnd);

        if ($boolCondition === self::FALSE && $comparisonOperator === '!==') {
            $tokens->insertAt($insertIndex, new Token([T_BOOL_CAST, '(bool)']));
            return;
        }

        if ($boolCondition === self::TRUE && $comparisonOperator === '===') {
            $tokens->insertAt($insertIndex, new Token([T_BOOL_CAST, '(bool)']));
            return;
        }

        if ($boolCondition === self::FALSE && $comparisonOperator === '===') {
            $tokens->insertAt($insertIndex, new Token('!'));
            return;
        }

        if ($boolCondition === self::TRUE && $comparisonOperator === '!==') {
            $tokens->insertAt($insertIndex, new Token('!'));
            return;
        }
    }

    private function isStrictValue(Token $token)
    {
        return in_array($token->getContent(), $this->strictValues, true);
    }
}
