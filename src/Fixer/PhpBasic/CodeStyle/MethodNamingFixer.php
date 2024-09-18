<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class MethodNamingFixer extends AbstractFixer
{
    public const BOOL_FUNCTION_COMMENT = 'Question-type functions always return boolean (https://bit.ly/psg-methods)';

    private array $boolFunctionPrefixes;

    public function __construct()
    {
        parent::__construct();

        $this->boolFunctionPrefixes = [
            'is',
            'are',
            'has',
            'can',
            'does',
        ];
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<TEXT
We use verbs for methods that perform action and/or return something,
questions only for methods which return boolean.

Questions start with has, is, can - these cannot make any side-effect and always return boolean.

For entities we use is* or are* for boolean getters, get* 
for other getters, set* for setters, add* for adders, remove* for removers.

We always make correct English phrase from method names,
this is more important that naming method to \'is\' + propertyName.
TEXT,
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    public function isThisNeeded()
    {
        return 123;
    }
    
    public function hasAllRights()
    {
        soSomething();
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
        return 'Paysera/php_basic_code_style_method_naming';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $functionTokenIndex ? $tokens->getPrevNonWhitespace($functionTokenIndex) : null;

            if (
                $functionTokenIndex
                && $visibilityTokenIndex
                && $token->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
                && $tokens[$visibilityTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            ) {
                $functionName = $tokens[$key]->getContent();
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $nextIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);

                $returnType = null;
                if ($tokens[$nextIndex]->isGivenKind(CT::T_TYPE_COLON)) {
                    $typeIndex = $tokens->getNextMeaningfulToken($nextIndex);
                    $returnType = $tokens[$typeIndex]->getContent();
                    $nextIndex = $tokens->getNextMeaningfulToken($typeIndex);
                }

                if (!$tokens[$nextIndex]->equals('{')) {
                    continue;
                }

                $this->fixMethod($tokens, $functionName, $visibilityTokenIndex, $nextIndex, $returnType);
            }
        }
    }

    private function fixMethod(
        Tokens $tokens,
        $functionName,
        $visibilityTokenIndex,
        $curlyBraceStartIndex,
        $returnType,
    ): void {
        $index = $tokens->getPrevNonWhitespace($visibilityTokenIndex);
        $docBlockIndex = null;
        if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
            $docBlockIndex = $index;
        } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
            $docBlockIndex = $tokens->getPrevNonWhitespace($index);
        }

        $shouldReturnBool = preg_match(
            '#^(' . implode('|', $this->boolFunctionPrefixes) . ')[A-Z]#',
            $functionName,
        );

        if (!$shouldReturnBool) {
            return;
        }

        if ($returnType !== null) {
            $returnsBool = $returnType === 'bool';
        } elseif ($docBlockIndex !== null) {
            $comment = $tokens[$docBlockIndex]->getContent();
            $returnsBool = preg_match('#@return\s+(boolean|bool)(\s|\n)#', $comment);
        } else {
            $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
            $returnsBool = $this->hasFunctionReturnClause($tokens, $curlyBraceStartIndex, $curlyBraceEndIndex);
        }

        if (!$returnsBool) {
            $this->insertComment($tokens, $curlyBraceStartIndex);
        }
    }

    private function hasFunctionReturnClause(Tokens $tokens, int $curlyBraceStartIndex, int $curlyBraceEndIndex): bool
    {
        for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; $i++) {
            if ($tokens[$i]->isGivenKind(T_RETURN)) {
                return true;
            }
        }

        return false;
    }

    private function insertComment(Tokens $tokens, int $insertIndex): void
    {
        $comment = '// TODO: ' . self::BOOL_FUNCTION_COMMENT;
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertSlices([
                ($insertIndex + 1) => [
                    new Token([T_WHITESPACE, ' ']),
                    new Token([T_COMMENT, $comment]),
                ],
            ]);
        }
    }
}
