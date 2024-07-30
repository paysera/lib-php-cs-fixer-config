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

final class ThrowBaseExceptionFixer extends AbstractFixer
{
    public const EXCEPTION = 'exception';
    public const CONVENTION = 'PhpBasic convention 3.20.1: We almost never throw base \Exception class';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'We never throw base \Exception class except if we don’t intend for it to be caught.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    public function sampleFunction()
    {
        throw new \Exception();
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
        return 'Paysera/php_basic_feature_throw_base_exception';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_THROW);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens): void
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
                        $tokens[$tokens->getNextMeaningfulToken($exceptionIndex)]->getContent(),
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

    private function insertComment(Tokens $tokens, int $endOfLineIndex)
    {
        $tokens->insertSlices([
            ($endOfLineIndex + 1) => [
                new Token([T_WHITESPACE, ' ']),
                new Token([T_COMMENT, '// TODO: ' . self::CONVENTION]),
            ],
        ]);
    }
}
