<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class PropertyNamingFixer extends AbstractFixer
{
    public const CONVENTION = 'PhpBasic convention 2.5.4: We do not use verbs or questions for property names';

    private array $invalidPropertyVerbs;
    private array $invalidPropertyPrefixes;
    private array $invalidPropertySuffixes;

    public function __construct()
    {
        parent::__construct();

        $this->invalidPropertyVerbs = [
            'check',
            'Check',
        ];
        $this->invalidPropertyPrefixes = [
            'is',
            'can',
            'has',
            'will',
            'would',
        ];
        $this->invalidPropertySuffixes = [
            'ing',
            'ded',
        ];
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'We use nouns or adjectives for property names, not verbs or questions.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    protected $isValid;
    private $canWork;
    private $willSlide;
    protected $check;
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
        return 'Paysera/php_basic_code_style_property_naming';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_VARIABLE);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if ($tokens[$key]->isGivenKind(T_VARIABLE)) {
                $previousTokenIndex = $tokens->getPrevNonWhitespace($key);
                $previousPreviousTokenIndex = $tokens->getPrevNonWhitespace($previousTokenIndex);
                if (
                    $tokens[$previousTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                    || (
                        $tokens[$previousTokenIndex]->isGivenKind(T_STATIC)
                        && $tokens[$previousPreviousTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                    )
                ) {
                    $this->validatePropertyName($tokens, $key);
                }
            }
        }
    }

    private function validatePropertyName(Tokens $tokens, int $key)
    {
        $propertyName = $tokens[$key]->getContent();
        $insertIndex = $tokens->getNextTokenOfKind($key, [';']);
        if (
            preg_match('#\$' . implode('|', $this->invalidPropertyVerbs) . '$#', $propertyName)
            || preg_match('#\$(' . implode('|', $this->invalidPropertyPrefixes) . ')([A-Z]).*#', $propertyName)
            || preg_match('#(' . implode('|', $this->invalidPropertySuffixes) . ')$#', $propertyName)
        ) {
            $this->insertComment($tokens, $insertIndex, $propertyName);
        }
    }

    private function insertComment(Tokens $tokens, int $insertIndex, string $propertyName)
    {
        $comment = '// TODO: "' . $propertyName . '" - ' . self::CONVENTION;
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertSlices([
                $insertIndex + 1 => [
                    new Token([T_WHITESPACE, ' ']),
                    new Token([T_COMMENT, $comment]),
                ],
            ]);
        }
    }
}
