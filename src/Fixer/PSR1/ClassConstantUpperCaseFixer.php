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

final class ClassConstantUpperCaseFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Ensures that constant names are all uppercase with underscores.',
            [
                new CodeSample(
                    <<<'PHP'
<?php 
class invalid_className 
{
    const class_constantLongName = 1;
    const classconstant = 2;
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
        return 'Paysera/psr_1_class_constant_upper_case';
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
        $invalidConstantNames = [];
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }
            $constantName = $this->getConstantName($tokens, $key);

            if ($constantName !== null) {
                if (preg_match('/^[A-Z][A-Z0-9_]*$/', $constantName) !== 0) {
                    continue;
                }
                $invalidConstantNames[] = $constantName;
            }

            if (in_array($token->getContent(), $invalidConstantNames, true)) {
                $tokens[$key] = new Token([$token->getId(), strtoupper($token->getContent())]);
//                $token->setContent(strtoupper($token->getContent()));
            }
        }
    }

    private function getConstantName(Tokens $tokens, int $key): ?string
    {
        $constantTokenKey = $tokens->getPrevNonWhitespace($key);
        if ($tokens[$constantTokenKey]->isGivenKind(T_CONST)) {
            return $tokens[$key]->getContent();
        }
        return null;
    }
}
