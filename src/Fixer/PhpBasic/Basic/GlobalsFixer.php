<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Basic;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class GlobalsFixer extends AbstractFixer
{
    const GLOBALS_VARIABLE = '$GLOBALS';
    const CONVENTION = 'PhpBasic convention 1.2: We do not use global variables, constants and functions.';

    public function getDefinition()
    {
        return new FixerDefinition(
            'Checks for global variables, constants and functions',
            [
                new CodeSample('
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
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_basic_globals';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function getPriority()
    {
        // Must run after SingleClassPerFileFixer
        return -1;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound(Token::getClassyTokenKinds());
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
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

    /**
     * @param Tokens $tokens
     * @param int $endIndex
     * @return bool
     */
    private function isGlobalFound(Tokens $tokens, $endIndex)
    {
        for ($i = 0; $i < $endIndex; $i++) {
            if ($tokens[$i]->isGivenKind(Token::getClassyTokenKinds())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param int $tokenIndex
     */
    private function addGlobalUsageWarning(Tokens $tokens, $key, $tokenIndex)
    {
        $tokens->insertAt($tokenIndex + 1, new Token([T_WHITESPACE, ' ']));
        $tokens->insertAt($tokenIndex + 2, new Token([
            T_COMMENT,
            '// TODO: "' . $tokens[$key]->getContent() . '" - ' . self::CONVENTION,
        ]));
    }
}
