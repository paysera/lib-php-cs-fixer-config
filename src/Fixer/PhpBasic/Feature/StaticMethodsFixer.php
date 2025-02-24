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

final class StaticMethodsFixer extends AbstractFixer
{
    public const CONVENTION = 'PhpBasic convention: Static methods, https://github.com/paysera/php-style-guide?tab=readme-ov-file#static-methods';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            self::CONVENTION . ' .',
            [
                new CodeSample(
                    <<<'PHP'
<?php
class MyTest {
    /**
     * @dataProvider getCases
     */
    public function test(): void
    {
        $this->assertTrue(true);
    }

    public function getCases(): array
    {
        return [['data']];
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
        return 'Paysera/php_basic_feature_static_methods';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function getPriority(): int
    {
        // Should run after `VisibilityRequiredFixer` and after `ArraySyntaxFixer` as short syntax
        return -10;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_STATIC, T_FUNCTION]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $functionNameIndex = $tokens->getNextMeaningfulToken($key);
            if (!$functionNameIndex || !$tokens[$functionNameIndex]->isGivenKind(T_STRING)) {
                continue;
            }

            $functionName = $tokens[$functionNameIndex]->getContent();

            // Check if this method is referenced as a data provider
            $isDataProvider = false;
            for ($i = 0; $i < count($tokens); $i++) {
                if ($tokens[$i]->isGivenKind(T_DOC_COMMENT)) {
                    $docComment = $tokens[$i]->getContent();
                    if (strpos($docComment, '@dataProvider ' . $functionName) !== false) {
                        $isDataProvider = true;
                        break;
                    }
                }

                // Check for PHP 8 attributes if available
                if (PHP_VERSION_ID >= 80000 && defined('T_ATTRIBUTE')) {
                    if ($tokens[$i]->isGivenKind(T_ATTRIBUTE)) {
                        $attributeEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_ATTRIBUTE, $i);
                        $attributeContent = $tokens->generatePartialCode($i, $attributeEnd);

                        // Match DataProvider('methodName') or DataProvider("methodName")
                        if (preg_match('/DataProvider\([\'"]' . preg_quote($functionName, '/') . '[\'"]\)/', $attributeContent)) {
                            $isDataProvider = true;
                            break;
                        }
                    }
                }
            }

            if (!$isDataProvider) {
                continue;
            }

            // Check if method is static
            $prevIndex = $tokens->getPrevMeaningfulToken($key);
            if ($prevIndex && $tokens[$prevIndex]->isGivenKind(T_STATIC)) {
                continue;
            }

            $nextTokenIndex = $tokens->getNextTokenOfKind($key, ['{']);
            if ($nextTokenIndex === null) {
                continue;
            }

            // Add comment if not already present
            if (!$tokens[$nextTokenIndex - 2]->isGivenKind(T_COMMENT)) {
                $tokens->insertSlices([
                    ($nextTokenIndex - 1) => [
                        new Token([T_WHITESPACE, ' ']),
                        new Token([
                            T_COMMENT,
                            '// TODO: "' . $functionName . '" - ' . self::CONVENTION,
                        ]),
                    ],
                ]);
            }
        }
    }

}
