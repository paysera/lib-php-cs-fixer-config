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

final class CheckingExplicitlyFixer extends AbstractFixer
{
    public const STRLEN = 'strlen';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<'TEXT'
We use only functions or conditions that are designed for specific task we are trying to accomplish. 
We don’t use unrelated features, even if they give required result with less code.

We avoid side-effects even if in current situation they are practically impossible.
For example, we use isset versus empty if we only want to check if array element is defined.
For example, we use $x !== \'\' instead of strlen($x) > 0 - length of $x
has nothing to do with what we are trying to check here, even if it gives us needed result.

For example, we use count($array) > 0 to check if array is not empty and not !empty($array),
as we do not want to check whether $array is 0, false, \'\' or even not defined at all
(in which case IDE would possibly hide some warnings that could help noticing possible bugs).
TEXT
            ,
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    public function __construct($arg1, $arg2)
    {
        if (strlen($arg1) > 0) {
            return $arg1;
        } elseif (!empty($arg2)) {
            return $arg2;
        }
    }
}

PHP,
                ),
            ],
            null,
            'Paysera recommendation.',
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_feature_checking_explicitly';
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_STRING) && $token->getContent() === self::STRLEN) {
                $this->fixStringLengthFunction($tokens, $key);
            }

            if ($token->isGivenKind(T_EMPTY)) {
                $this->fixEmptyFunction($tokens, $key);
            }
        }
    }

    private function fixStringLengthFunction(Tokens $tokens, $key)
    {
        $parenthesesStartIndex = $tokens->getNextMeaningfulToken($key);
        if (!$tokens[$parenthesesStartIndex]->equals('(')) {
            return;
        }
        $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parenthesesStartIndex);

        $greaterIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);
        if (!$tokens[$greaterIndex]->equals('>')) {
            return;
        }

        $zeroIndex = $tokens->getNextMeaningfulToken($greaterIndex);
        if ($tokens[$zeroIndex]->getContent() !== '0') {
            return;
        }

        $tokens->overrideAt($zeroIndex, new Token([T_CONSTANT_ENCAPSED_STRING, "''"]));
        $tokens->overrideAt($greaterIndex, new Token([T_IS_NOT_IDENTICAL, '!==']));
        $tokens->clearRange($key, $key);
    }

    private function fixEmptyFunction(Tokens $tokens, $key)
    {
        if ($this->tryFixEmptyToIsset($tokens, $key)) {
            return;
        }

        if ($this->tryFixNotEmptyToCount($tokens, $key)) {
            return;
        }
    }

    private function tryFixNotEmptyToCount(Tokens $tokens, $key)
    {
        $notOperatorIndex = $tokens->getPrevMeaningfulToken($key);
        $negation = false;
        if ($tokens[$notOperatorIndex]->equals('!')) {
            $negation = true;
        }

        $parenthesesStartIndex = $tokens->getNextMeaningfulToken($key);
        if (!$tokens[$parenthesesStartIndex]->equals('(')) {
            return false;
        }
        $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parenthesesStartIndex);

        $tokens->offsetSet($key, new Token([T_STRING, 'count']));
        $tokens->insertSlices([++$parenthesesEndIndex => [new Token([T_WHITESPACE, ' '])]]);
        if ($negation) {
            $tokens->insertSlices([++$parenthesesEndIndex => [new Token('>')]]);
            $tokens->clearAt($notOperatorIndex);
        } else {
            $tokens->insertSlices([++$parenthesesEndIndex => [new Token([T_IS_IDENTICAL, '==='])]]);
        }
        $tokens->insertSlices([
            ++$parenthesesEndIndex => [
                new Token([T_WHITESPACE, ' ']),
                new Token([T_LNUMBER, '0']),
            ]
        ]);

        return true;
    }

    private function tryFixEmptyToIsset(Tokens $tokens, $key): bool
    {
        $emptyBraceStart = $tokens->getNextMeaningfulToken($key);
        $emptyBraceEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $emptyBraceStart);

        $arrayAccessStart = $tokens->getNextTokenOfKind($emptyBraceStart, ['[']);
        $arrayAccessEnd = null;
        if ($arrayAccessStart !== null) {
            $arrayAccessEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_INDEX_SQUARE_BRACE, $arrayAccessStart);
        }

        $notOperatorIndex = $tokens->getPrevMeaningfulToken($key);
        $negation = false;
        if ($tokens[$notOperatorIndex]->equals('!')) {
            $negation = true;
        }

        if (
            $arrayAccessStart !== null
            && $arrayAccessEnd !== null
            && $arrayAccessStart > $emptyBraceStart
            && $arrayAccessEnd < $emptyBraceEnd
        ) {
            $tokens->offsetSet($key, new Token([T_ISSET, 'isset']));
            if ($negation) {
                $tokens->clearAt($notOperatorIndex);
            } else {
                $tokens->insertSlices([$notOperatorIndex + 1 => [new Token('!')]]);
            }
            return true;
        }

        return false;
    }
}
