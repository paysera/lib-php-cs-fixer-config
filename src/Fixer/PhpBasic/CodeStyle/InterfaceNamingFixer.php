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

final class InterfaceNamingFixer extends AbstractFixer
{
    public const INTERFACE_NAME = 'Interface';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<'TEXT'
We always add suffix Interface to interfaces, even if interface name would be adjective.
Risky for renaming interface name.
TEXT,
            [
                new CodeSample(
                    <<<'PHP'
<?php
interface Sample
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
        return 'Paysera/php_basic_code_style_interface_naming';
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_INTERFACE);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_INTERFACE)) {
                continue;
            }

            $interfaceNameIndex = $tokens->getNextMeaningfulToken($key);
            if (!$tokens[$interfaceNameIndex]->isGivenKind(T_STRING)) {
                continue;
            }

            $this->validateInterfaceName($tokens, $interfaceNameIndex);
        }
    }

    private function validateInterfaceName(Tokens $tokens, int $interfaceNameIndex)
    {
        $interfaceName = $tokens[$interfaceNameIndex]->getContent();
        if (!preg_match('#(' . self::INTERFACE_NAME . ')#', $interfaceName)) {
            $tokens[$interfaceNameIndex] = new Token(
                [
                    $tokens[$interfaceNameIndex]->getId(),
                    $tokens[$interfaceNameIndex]->getContent() . self::INTERFACE_NAME,
                ],
            );
        }
    }
}
