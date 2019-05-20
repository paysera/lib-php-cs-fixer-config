<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class TraitsFixer extends AbstractFixer
{
    const CONVENTION = 'PhpBasic convention 3.23: We do not use traits';

    public function getDefinition()
    {
        return new FixerDefinition(
            'The only valid case for traits is in unit test classes. We do not use traits in our base code.',
            [
                new CodeSample('
                <?php
                    trait TraitSample
                    {
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_traits';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_TRAIT);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_TRAIT)) {
                $commentTokenIndex = $tokens->getPrevNonWhitespace($key);
                $traitName = $tokens[$tokens->getNextMeaningfulToken($key)]->getContent();
                if (!$tokens[$commentTokenIndex]->isGivenKind(T_COMMENT)) {
                    $tokens->insertAt($key++, new Token([
                        T_COMMENT,
                        '// TODO: "' . $traitName . '" - ' . self::CONVENTION,
                    ]));
                    $tokens->insertAt($key, new Token([T_WHITESPACE, "\n"]));
                    break;
                }
            }
        }
    }
}
