<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class FunctionIsNullFixer extends AbstractFixer
{
    const IS_NULL = 'is_null';

    public function getDefinition()
    {
        return new FixerDefinition(
            'We use compare to null using === instead of is_null function.',
            [
                new CodeSample('
                <?php
                class Sample
                {
                    public function someFunction()
                    {
                        $a = null;
                        return is_null($a);
                    }
                }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_function_is_null';
    }

    public function getPriority()
    {
        // Should run after `UnaryOperatorSpacesFixer`
        return -10;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
                && $token->getContent() === self::IS_NULL
            ) {
                if ($tokens[$key - 1]->equals('!')) {
                    $this->fixIsNullFunction($tokens, $key - 1, $key, false);
                } else {
                    $this->fixIsNullFunction($tokens, $key, $key, true);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $startClearIndex
     * @param int $endClearIndex
     * @param bool $identical
     */
    private function fixIsNullFunction(Tokens $tokens, $startClearIndex, $endClearIndex, $identical)
    {
        $endParenthesesIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $endClearIndex + 1);
        $tokens->insertAt(++$endParenthesesIndex, new Token([T_WHITESPACE, ' ']));
        $tokens->insertAt(
            ++$endParenthesesIndex,
            new Token($identical ? [T_IS_IDENTICAL, '==='] : [T_IS_NOT_IDENTICAL, '!=='])
        );
        $tokens->insertAt(++$endParenthesesIndex, new Token([T_WHITESPACE, ' ']));
        $tokens->insertAt(++$endParenthesesIndex, new Token([T_STRING, 'null']));

        $tokens->clearRange($startClearIndex, $endClearIndex);
    }
}
