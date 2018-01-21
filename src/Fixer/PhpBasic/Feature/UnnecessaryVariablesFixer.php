<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;

final class UnnecessaryVariablesFixer extends AbstractFixer
{
    const DEFAULT_CHARACTER_LENGTH = 45;
    const USED_COUNT = 2;
    const RETURN_COUNT = 1;
    const VALID_VARIABLE_LENGTH = 8;

    private $methodsToSkip = ['flush'];

    public function getDefinition()
    {
        return new FixerDefinition(
            'We avoid unnecessary variables. Risky for possible incompatibility',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            private function getSomething()
                            {
                                $a = get();
                                $b = getAll();
                                if ($a) {
                                    return $a;
                                } else {
                                    return $b;
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
        return 'Paysera/php_basic_feature_unnecessary_variables';
    }

    public function isRisky()
    {
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_VARIABLE);
    }

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        if ($this->configuration['methods_to_skip'] === true) {
            return;
        }
        if (isset($this->configuration['methods_to_skip'])) {
            $this->methodsToSkip = $this->configuration['methods_to_skip'];
        }
    }

    protected function createConfigurationDefinition()
    {
        $options = new FixerOptionBuilder(
            'methods_to_skip',
            'If methods are found to be called, then fix will not be applied.'
        );

        $options = $options
            ->setAllowedTypes(['array', 'bool'])
            ->getOption()
        ;

        return new FixerConfigurationResolverRootless('methods_to_skip', [$options]);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }
            $parenthesesStartIndex = $tokens->getNextMeaningfulToken($key);
            if (!$tokens[$tokens->getPrevMeaningfulToken($key)]->isGivenKind(T_FUNCTION)
                && !$tokens[$parenthesesStartIndex]->equals('(')
            ) {
                continue;
            }
            $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parenthesesStartIndex);

            $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);
            if (!$tokens[$curlyBraceStartIndex]->equals('{')) {
                continue;
            }
            $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
            $this->validateVariables($tokens, $curlyBraceStartIndex, $curlyBraceEndIndex);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $curlyBraceStartIndex
     * @param int $curlyBraceEndIndex
     */
    private function validateVariables(Tokens $tokens, $curlyBraceStartIndex, $curlyBraceEndIndex)
    {
        $variables = [];
        for ($index = $curlyBraceStartIndex; $index < $curlyBraceEndIndex; ++$index) {
            $nextTokenIndex = $tokens->getNextMeaningfulToken($index);
            $nextNextTokenIndex = $tokens->getNextMeaningfulToken($nextTokenIndex);
            if ($tokens[$index]->isGivenKind(T_VARIABLE)
                && $tokens[$nextTokenIndex]->equals('=')
                && strpos($tokens[$index - 1]->getContent(), "\n") !== false
                && !$tokens[$index - 2]->isGivenKind(T_DOC_COMMENT)
                && !in_array($tokens[$index]->getContent(), $variables, true)
            ) {
                $variable = null;
                $variable = $this->getVariableValue($tokens, $index, $nextNextTokenIndex);
                $this->checkForUnnecessaryVariable($tokens, $curlyBraceStartIndex, $curlyBraceEndIndex, $variable);
                $variables[] = $tokens[$index]->getContent();
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $variableIndex
     * @param int $valueStartIndex
     * @return array
     */
    private function getVariableValue(Tokens $tokens, $variableIndex, $valueStartIndex)
    {
        $variable['variableIndex'] = $variableIndex;
        $variable['valueStartIndex'] = $valueStartIndex;
        $variable['valueEndIndex'] = $tokens->getNextTokenOfKind($variableIndex, [';']) - 1;
        $variable['useCount'] = 0;
        $variable['returnCount'] = 0;
        $variable['content'] = '';
        for ($i = $variable['valueStartIndex']; $i <= $variable['valueEndIndex']; $i++) {
            $variable['valueTokens'][] = $tokens[$i];
            $variable['content'] .= $tokens[$i]->getContent();
        }

        return $variable;
    }

    /**
     * @param Tokens $tokens
     * @param int $curlyBraceStartIndex
     * @param int $curlyBraceEndIndex
     * @param array $variable
     */
    private function checkForUnnecessaryVariable(Tokens $tokens, $curlyBraceStartIndex, $curlyBraceEndIndex, $variable)
    {
        $skipFix = false;
        for ($i = $curlyBraceStartIndex; $i <= $curlyBraceEndIndex; $i++) {
            // All variable usages
            if ($tokens[$i]->isGivenKind(T_VARIABLE)
                && $tokens[$i]->getContent() === $tokens[$variable['variableIndex']]->getContent()
            ) {
                $variable['useCount'] += 1;
            }

            // Usages on return statements
            if ($tokens[$i]->isGivenKind(T_RETURN)) {
                $return = $this->usageOnReturnStatement($tokens, $i, $variable['variableIndex']);
                if ($return !== null && $return['returnCount'] === self::RETURN_COUNT) {
                    $variable['returnCount'] = $return['returnCount'];
                    $variable['replacementIndex'] = $return['replacementIndex'];
                }
            }
            if (
                $tokens[$i]->isGivenKind(T_OBJECT_OPERATOR)
                && in_array($tokens[$i + 1]->getContent(), $this->methodsToSkip, true)
            ) {
                $skipFix = true;
            }
        }

        if (count($variable) === self::VALID_VARIABLE_LENGTH
            && $variable['useCount'] === self::USED_COUNT
            && $variable['returnCount'] === self::RETURN_COUNT
            && strlen($variable['content']) <= self::DEFAULT_CHARACTER_LENGTH
            && !$skipFix
        ) {
            $this->fixUnnecessaryVariable($tokens, $variable);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $returnIndex
     * @param int $variableIndex
     * @return array
     */
    private function usageOnReturnStatement(Tokens $tokens, $returnIndex, $variableIndex)
    {
        $index = $returnIndex;
        $variable['returnCount'] = 0;
        $variable['replacementIndex'] = null;
        while (!$tokens[$index]->equals(';')) {
            $index++;
            $nextTokenIndex = $tokens->getNextMeaningfulToken($index);
            if ($tokens[$index]->isGivenKind(T_VARIABLE)
                && !$tokens[$nextTokenIndex]->equals('=')
                && !$tokens[$nextTokenIndex]->isGivenKind(T_OBJECT_OPERATOR)
                && $tokens[$index]->getContent() === $tokens[$variableIndex]->getContent()
            ) {
                $variable['returnCount'] += 1;
                $variable['replacementIndex'] = $index;
            }
        }
        return $variable;
    }

    /**
     * @param Tokens $tokens
     * @param array $variable
     */
    private function fixUnnecessaryVariable(Tokens $tokens, $variable)
    {
        $tokens->overrideRange(
            $variable['replacementIndex'],
            $variable['replacementIndex'],
            $variable['valueTokens']
        );
        $tokens->clearRange($variable['variableIndex'] - 1, $variable['valueEndIndex'] + 1);
    }
}
