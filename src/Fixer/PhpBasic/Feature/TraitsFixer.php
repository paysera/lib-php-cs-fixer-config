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

final class TraitsFixer extends AbstractFixer
{
    public const CONVENTION = 'PhpBasic convention 3.23: We do not use traits';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'The only valid case for traits is in unit test classes. We do not use traits in our base code.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
trait TraitSample
{
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
        return 'Paysera/php_basic_feature_traits';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_TRAIT);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_TRAIT)) {
                $commentTokenIndex = $tokens->getPrevNonWhitespace($key);
                $traitName = $tokens[$tokens->getNextMeaningfulToken($key)]->getContent();
                if (!$tokens[$commentTokenIndex]->isGivenKind(T_COMMENT)) {
                    $tokens->insertSlices([
                        $key => [
                            new Token([
                                T_COMMENT,
                                '// TODO: "' . $traitName . '" - ' . self::CONVENTION,
                            ]),
                            new Token([T_WHITESPACE, "\n"]),
                        ],
                    ]);
                    break;
                }
            }
        }
    }
}
