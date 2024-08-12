<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PSR1;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ClassNameStudlyCapsFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Ensures classes are in StudlyCaps, and the first letter is capitalised.',
            [
                new CodeSample(
                    <<<'PHP'
<?php 
class invalid_className {}

PHP,
                ),
            ],
            null,
            'Paysera recommendation.',
        );
    }

    public function getName(): string
    {
        return 'Paysera/psr_1_class_name_studly_caps';
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $invalidClassNames = [];
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }
            $className = $this->getClassName($tokens, $key);

            if ($className !== null) {
                if ($this->isValidClassName($className)) {
                    continue;
                }
                $invalidClassNames[] = $className;
            }

            if (in_array($token->getContent(), $invalidClassNames, true)) {
                $tokens[$key] = new Token([$token->getId(), $this->fixClassName($token->getContent())]);
            }
        }
    }

    private function getClassName(Tokens $tokens, int $key): ?string
    {
        $classTokenKey = $tokens->getPrevNonWhitespace($key);
        if ($tokens[$classTokenKey]->isGivenKind([T_CLASS, T_INTERFACE, T_TRAIT])) {
            return $tokens[$key]->getContent();
        }

        return null;
    }

    private function isValidClassName(string $string): bool
    {
        return
            !(preg_match('/^[A-Z]/', $string) === 0)
            && !(preg_match('|[^a-zA-Z0-9]|', substr($string, 1)) > 0)
        ;
    }

    private function fixClassName(string $className): string
    {
        $string = $className;
        $string = preg_replace('/[^a-z0-9' . ']+/i', ' ', $string);
        $string = trim($string);

        $string = ucwords($string);
        $string = strtr($string, [' ' => '']);

        return ucfirst($string);
    }
}
