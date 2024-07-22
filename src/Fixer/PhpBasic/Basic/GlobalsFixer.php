<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Basic;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class GlobalsFixer extends AbstractFixer
{
    public const GLOBALS_VARIABLE = '$GLOBALS';
    public const CONVENTION = 'PhpBasic convention 1.2: We do not use global variables, constants and functions.';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Checks for global variables, constants and functions.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
    function globalFunction(){
        return 0;
    }
    const GLOBAL_CONSTANT = 1;
    $GLOBALS["a"] = "a";
    global $variable;
    $variable = 1;
    
    class Sample
    {
        public function sampleFunction()
        {
            global $variable;
            $globalVar = $variable;
            $globalVariable = $GLOBALS["a"];
            $globalFunction = globalFunction();
            $globalConstant = GLOBAL_CONSTANT;
        }
    }

PHP,
                ),
            ],
            null,
            'Paysera recommendation.'
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_basic_globals';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function getPriority(): int
    {
        // Must run after SingleClassPerFileFixer
        return -1;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds());
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($key = 0; $key < $tokens->count(); $key++) {
            if (
                $tokens[$key]->isGivenKind(T_GLOBAL)
                || ($tokens[$key]->isGivenKind(T_VARIABLE) && $tokens[$key]->getContent() === self::GLOBALS_VARIABLE)
            ) {
                $tokenIndex = $tokens->getNextTokenOfKind($key, [';']);
                if (!$this->isGlobalFound($tokens, $key) && !$tokens[$tokenIndex + 2]->isGivenKind(T_COMMENT)) {
                    $this->addGlobalUsageWarning($tokens, $key, $tokenIndex);
                }
            }

            if ($tokens[$key]->isGivenKind([T_CONST, T_VARIABLE])) {
                $tokenIndex = $tokens->getNextTokenOfKind($key, [';']);
                if (!$this->isGlobalFound($tokens, $key) && !$tokens[$tokenIndex + 2]->isGivenKind(T_COMMENT)) {
                    $this->addGlobalUsageWarning($tokens, $key, $tokenIndex);
                }
            }

            if ($tokens[$key]->isGivenKind([T_FUNCTION])) {
                $tokenIndex = $tokens->getNextTokenOfKind($key, ['}']);
                if (!$this->isGlobalFound($tokens, $key) && !$tokens[$tokenIndex + 2]->isGivenKind(T_COMMENT)) {
                    $this->addGlobalUsageWarning($tokens, $key, $tokenIndex);
                }
            }
        }
    }

    private function isGlobalFound(Tokens $tokens, $endIndex): bool
    {
        for ($i = 0; $i < $endIndex; $i++) {
            if ($tokens[$i]->isGivenKind(Token::getClassyTokenKinds())) {
                return true;
            }
        }

        return false;
    }

    private function addGlobalUsageWarning(Tokens $tokens, $key, $tokenIndex)
    {
        $tokens->insertSlices([
            $tokenIndex + 1 => [
                new Token([T_WHITESPACE, ' ']),
                new Token([
                    T_COMMENT,
                    '// TODO: "' . $tokens[$key]->getContent() . '" - ' . self::CONVENTION,
                ]),
            ],
        ]);
    }
}
