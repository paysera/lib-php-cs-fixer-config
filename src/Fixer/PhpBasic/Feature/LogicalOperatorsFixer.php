<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class LogicalOperatorsFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<'TEXT'
Use `&&` and `||` logical operators instead of `and` and `or`.
Risky, if lower precedence was used intentionally.
TEXT
            ,
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    private function sampleFunction()
    {
        $a = $d and $e ? false : true;
        
        if ($a or $b) {
            return ($a and $b or $c and $d);
        }
        
        return $c or $d;
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
        return 'Paysera/php_basic_feature_logical_operators';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_LOGICAL_AND, T_LOGICAL_OR]);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_LOGICAL_AND)) {
                $tokens->overrideRange($key, $key, [T_BOOLEAN_AND, '&&']);
            } elseif ($token->isGivenKind(T_LOGICAL_OR)) {
                $tokens->overrideRange($key, $key, [T_BOOLEAN_OR, '||']);
            }
        }
    }
}
