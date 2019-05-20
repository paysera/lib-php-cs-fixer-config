<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Basic;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class SingleClassPerFileFixer extends AbstractFixer
{
    const CONVENTION = 'PhpBasic convention 1.3: Only one class/interface can be declared per file';

    public function getDefinition()
    {
        return new FixerDefinition(
            'Checks if there is one class per file.',
            [
                new CodeSample('
                <?php
                    class ClassOne
                    {
                    }
                    class ClassTwo
                    {
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_basic_single_class_per_file';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds());
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $tokenCount = $tokens->count();
        $classCount = 0;

        for ($key = 0; $key < $tokenCount; $key++) {
            if ($tokens[$key]->isGivenKind(Token::getClassyTokenKinds())) {
                $classCount++;
            }

            if ($classCount > 1 && !$tokens[$tokenCount - 1]->isGivenKind(T_COMMENT)) {
                $tokens->insertAt(
                    $tokenCount,
                    new Token([T_COMMENT, '// TODO: "' . $tokens[$key]->getContent() . '" - ' . self::CONVENTION])
                );
                $tokens->insertAt($tokenCount, new Token([T_WHITESPACE, "\n"]));
                break;
            }
        }
    }
}
