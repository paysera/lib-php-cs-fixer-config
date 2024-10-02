<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class DefaultValuesInConstructorFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public const CONSTRUCT = '__construct';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'We declare default variables in constructor.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
                    
class Sample
{
    private $array = [];
    private $integer = 1;
    
    public function __construct()
    {
    }
}

PHP,
                ),
            ],
            null,
            'Paysera recommendation.',
        );
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_code_style_default_values_in_constructor';
    }

    public function getPriority(): int
    {
        // Should run after `VisibilityPropertiesFixer`
        return 61;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_CLASS]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $parentConstructNeeded = false;
        $isConstructorPresent = false;
        $propertiesWithDefaultValues = [];

        foreach ($tokens as $key => $token) {
            if ($this->isConstructor($key, $tokens, $token)) {
                $isConstructorPresent = true;
            }
        }

        $classFound = false;
        foreach ($tokens as $key => $token) {
            if (!$classFound) {
                $classFound = $token->isGivenKind([T_CLASS]);
                continue;
            }

            $extends = $token->isGivenKind([T_EXTENDS]);
            if ($extends !== false) {
                $parentConstructNeeded = true;
            }

            $subsequentDeclarativeToken = $tokens->getNextMeaningfulToken($key);

            // @TODO: PHP 7.4 support, drop condition when there will be no PHP 7.4 support.
            $tokenKinds = [T_STATIC, T_FUNCTION];
            if (defined('T_READONLY')) {
                $tokenKinds[] = T_READONLY;
            }

            if (
                $token->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                && !$tokens[$subsequentDeclarativeToken]->isGivenKind($tokenKinds)
            ) {
                $propertyNameIndex = $tokens->getNextNonWhitespace($key);
                $endOfPropertyDeclarationSemicolon = $tokens->getNextTokenOfKind($key, [';']);

                if ($tokens[$propertyNameIndex]->isGivenKind(T_CONST)) {
                    continue;
                }

                if ($tokens[$propertyNameIndex]->isGivenKind(10022)) {
                    $propertyNameIndex = $tokens->getNextNonWhitespace($propertyNameIndex);
                }

                if (!$tokens[$propertyNameIndex]->isGivenKind(T_VARIABLE)) {
                    $propertyNameIndex = $tokens->getNextNonWhitespace($propertyNameIndex);
                }

                if ($tokens[$tokens->getNextMeaningfulToken($propertyNameIndex)]->equals(';')) {
                    continue;
                }

                for ($i = $propertyNameIndex + 1; $i < $endOfPropertyDeclarationSemicolon; $i++) {
                    $propertiesWithDefaultValues[$tokens[$propertyNameIndex]->getContent()][] = $tokens[$i];
                }
                $tokens->clearRange($propertyNameIndex + 1, $endOfPropertyDeclarationSemicolon - 1);
            }

            if ($this->isConstructor($key, $tokens, $token)) {
                $curlyBracesStartIndex = $tokens->getNextTokenOfKind($key, ['{']);
                $indentation = $tokens[$curlyBracesStartIndex + 1]->getContent();
                $curlyBracesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBracesStartIndex);

                $previousMeaningfulToken = $tokens->getPrevMeaningfulToken($curlyBracesEndIndex);

                if ($tokens[$previousMeaningfulToken]->equals('{')) {
                    $indentation .= $this->whitespacesConfig->getIndent();
                }

                $this->insertDefaultPropertiesInConstruct(
                    $tokens,
                    $previousMeaningfulToken,
                    $indentation,
                    $propertiesWithDefaultValues,
                );
            }

            if (
                $this->isEndOfPropertiesDeclaration($key, $tokens, $token)
                && !$isConstructorPresent
                && count($propertiesWithDefaultValues) > 0
            ) {
                $endOfDeclarationNewLine = $tokens[$key + 1];

                $closingCurlyBrace = $tokens->getNextTokenOfKind($key, ['}']);
                $indentation = $tokens[$closingCurlyBrace - 1]->getContent();
                $index = $this->insertConstructTokensAndReturnOpeningBraceIndex(
                    $tokens,
                    $key + 1,
                    $indentation,
                );
                $tokens->insertSlices([
                    ($index + 3) => [new Token([T_WHITESPACE, $endOfDeclarationNewLine->getContent()])],
                ]);

                if ($tokens[$index]->equals('{')) {
                    $indentation .= $this->whitespacesConfig->getIndent();
                }
                if ($parentConstructNeeded) {
                    $index = $this->insertParentConstructAndReturnIndex($tokens, $index, $indentation);
                }

                $this->insertDefaultPropertiesInConstruct($tokens, $index, $indentation, $propertiesWithDefaultValues);

                return;
            }
        }
    }

    private function insertDefaultPropertiesInConstruct(
        Tokens $tokens,
        int $index,
        string $indentation,
        array $propertiesWithDefaultValues
    ): void {
        foreach ($propertiesWithDefaultValues as $name => $propertyTokens) {
            $tokens->insertSlices([
                ($index + 1) => [
                    new Token([T_WHITESPACE, $indentation]),
                    new Token([T_VARIABLE, '$this']),
                    new Token([T_OBJECT_OPERATOR, '->']),
                    new Token([T_STRING, str_replace('$', '', $name)]),
                ],
            ]);
            $index += 4;

            /** @var Token $item */
            foreach ($propertyTokens as $item) {
                if (strpos($item->getContent(), "\n") !== false) {
                    $itemIndentation = $item->getContent() . $this->whitespacesConfig->getIndent();
                    $tokens->insertSlices([++$index => [new Token([T_WHITESPACE, $itemIndentation])]]);
                } else {
                    $tokens->insertSlices([++$index => [$item]]);
                }
            }
            $tokens->insertSlices([++$index => [new Token(';')]]);

            $tokens->getPrevMeaningfulToken($index + 1);
        }
    }

    private function isEndOfPropertiesDeclaration(int $key, Tokens $tokens, Token $token): bool
    {
        if ($token->equals(';')) {
            $nextMeaningfulToken = $tokens->getNextMeaningfulToken($key);

            if (!$tokens[$nextMeaningfulToken]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])) {
                return true;
            }

            for ($i = 0; $i < 2; $i++) {
                $nextMeaningfulToken = $tokens->getNextNonWhitespace($nextMeaningfulToken);

                if ($tokens[$nextMeaningfulToken]->isGivenKind(T_FUNCTION)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isConstructor(int $key, Tokens $tokens, Token $token): bool
    {
        $functionTokenIndex = $tokens->getPrevNonWhitespace($key);

        return
            $tokens[$key]->isGivenKind(T_STRING)
            && $token->getContent() === self::CONSTRUCT
            && $tokens[$key + 1]->equals('(')
            && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
        ;
    }

    private function insertConstructTokensAndReturnOpeningBraceIndex(
        Tokens $tokens,
        int $index,
        string $indentation
    ): int {
        $openingCurlyBrace = $index + 9;
        $tokens->insertSlices([
            ++$index => [
                new Token([T_PUBLIC, 'public']),
                new Token([T_WHITESPACE, ' ']),
                new Token([T_FUNCTION, 'function']),
                new Token([T_WHITESPACE, ' ']),
                new Token([T_STRING, self::CONSTRUCT]),
                new Token('('),
                new Token(')'),
                new Token([T_WHITESPACE, $indentation]),
                new Token('{'),
                new Token([T_WHITESPACE, $indentation]),
                new Token('}'),
            ],
        ]);

        return $openingCurlyBrace;
    }

    private function insertParentConstructAndReturnIndex(Tokens $tokens, int $index, string $indentation): int
    {
        $tokens->insertSlices([
            ($index + 1) => [
                new Token([T_WHITESPACE, $indentation]),
                new Token([T_STRING, 'parent']),
                new Token([T_DOUBLE_COLON, '::']),
                new Token([T_STRING, self::CONSTRUCT]),
                new Token('('),
                new Token(')'),
                new Token(';'),
            ],
        ]);

        return $index + 7;
    }
}
