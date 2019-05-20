<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ThrowBaseExceptionFixer extends AbstractFixer
{
    const EXCEPTION = 'exception';
    const CONVENTION = 'PhpBasic convention 3.20.1: We almost never throw base \Exception class';

    public function getDefinition()
    {
        return new FixerDefinition(
            'We never throw base \Exception class except if we don’t intend for it to be caught.',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            public function sampleFunction()
                            {
                                throw new \Exception();
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_throw_base_exception';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_THROW);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_THROW)) {
                continue;
            }

            $newIndex = $tokens->getNextMeaningfulToken($key);
            if ($newIndex === null || !$tokens[$newIndex]->isGivenKind(T_NEW)) {
                continue;
            }

            $exceptionIndex = $tokens->getNextMeaningfulToken($newIndex);
            if (
                strtolower($tokens[$exceptionIndex]->getContent()) === self::EXCEPTION
                || (
                    $tokens[$exceptionIndex]->isGivenKind(T_NS_SEPARATOR)
                    && strtolower(
                        $tokens[$tokens->getNextMeaningfulToken($exceptionIndex)]->getContent()
                    ) === self::EXCEPTION
                )
            ) {
                $endOfLineIndex = $tokens->getNextTokenOfKind($key, [';']);
                $commentIndex = $tokens->getNextNonWhitespace($endOfLineIndex);
                if ($commentIndex === null || !$tokens[$commentIndex]->isGivenKind(T_COMMENT)) {
                    $this->insertComment($tokens, $endOfLineIndex);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $endOfLineIndex
     */
    private function insertComment(Tokens $tokens, $endOfLineIndex)
    {
        $tokens->insertAt($endOfLineIndex + 1, new Token([T_COMMENT, '// TODO: ' . self::CONVENTION]));
        $tokens->insertAt($endOfLineIndex + 1, new Token([T_WHITESPACE, ' ']));
    }
}
