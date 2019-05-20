<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class VoidResultFixer extends AbstractFixer
{
    const CONVENTION = 'PhpBasic convention 3.17.5: We always return something or return nothing';

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We always return something or return nothing. If method does not return anything (“returns” void),
            we do not return null, false or any other value in that case.
            
            If method must return some value, we always specify what to return, even when returning null.
            ',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            function getValue(MyObject $object)
                            {
                                if (!$object->has()) {
                                    return;
                                }
                                return $object->get();
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_void_result';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $tokens->getPrevNonWhitespace($functionTokenIndex);
            if (
                $token->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
                && $tokens[$visibilityTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            ) {
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);

                if ($tokens[$curlyBraceStartIndex]->equals('{')) {
                    $this->validateReturnTypes($tokens, $curlyBraceStartIndex);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $curlyBraceStartIndex
     */
    private function validateReturnTypes(Tokens $tokens, $curlyBraceStartIndex)
    {
        $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);

        $returnExists = false;
        $voidReturn = [];
        $funcStart = null;
        $funcEnd = null;

        for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; $i++) {
            if ($tokens[$i]->isGivenKind(T_FUNCTION)) {
                $funcStart = $tokens->getNextTokenOfKind($i, ['{']);
                if ($funcStart !== null) {
                    $funcEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $funcStart);
                }
            }
            if ($tokens[$i]->isGivenKind(T_RETURN)) {
                $returnValue = $tokens[$tokens->getNextMeaningfulToken($i)];
                if (
                    $returnValue->getContent() !== ';'
                    && (
                        ($funcStart === null && $funcEnd === null)
                        || ($funcStart !== null && $funcEnd !== null && ($i < $funcStart || $i > $funcEnd))
                    )
                ) {
                    $returnExists = true;
                }

                if ($returnValue->equals(';')) {
                    $voidReturn[] = $i;
                }
            }
        }

        if ($returnExists && count($voidReturn) > 0) {
            foreach (array_reverse($voidReturn) as $return) {
                $this->insertComment(
                    $tokens,
                    $return + 1
                );
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     */
    private function insertComment(Tokens $tokens, $insertIndex)
    {
        $comment = '// TODO: ' . self::CONVENTION;
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt(++$insertIndex, new Token([T_WHITESPACE, ' ']));
            $tokens->insertAt(++$insertIndex, new Token([T_COMMENT, $comment]));
        }
    }
}
