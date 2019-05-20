<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PSR1;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class FunctionNameCamelCaseFixer extends AbstractFixer
{
    public function getDefinition()
    {
        return new FixerDefinition(
            'Ensures function names are defined using camel case.',
            [
                new CodeSample('
                <?php 
                    class Sample 
                    {
                        private function invalid_function_name(){}
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/psr_1_function_name_camel_case';
    }

    public function isRisky()
    {
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $invalidFunctionNames = [];
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }
            $functionName = $this->getFunctionName($tokens, $key);

            if ($functionName !== null) {
                if ($this->isFunctionNameValid($functionName)) {
                    continue;
                }
                $invalidFunctionNames[] = $functionName;
            }
        }

        // Second foreach loop to check if there are functions used before their declaration
        foreach ($tokens as $token) {
            if (in_array($token->getContent(), $invalidFunctionNames, true)) {
                $token->setContent($this->fixFunctionName($token->getContent()));
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @return null|string
     */
    private function getFunctionName(Tokens $tokens, $key)
    {
        $constantTokenKey = $tokens->getPrevNonWhitespace($key);
        if ($tokens[$constantTokenKey]->isGivenKind([T_FUNCTION])) {
            return $tokens[$key]->getContent();
        }
        return null;
    }

    /**
     * @param string $functionName
     * @return string
     */
    private function fixFunctionName($functionName)
    {
        $string = $functionName;
        $string = preg_replace('/[^a-z0-9]+/i', ' ', $string);
        $string = trim($string);

        $string = ucwords($string);
        $string = strtr($string, [' ' => '']);
        return lcfirst($string);
    }

    /**
     * @param string $string
     * @return bool
     */
    private function isFunctionNameValid($string)
    {
        return
            preg_match('#^__[^_]#', $string) !== 0
            || (
                !(preg_match('|[^a-zA-Z]|', substr($string, 1)) > 0)
                && !(preg_match('/^[a-z]/', $string) === 0)
            )
        ;
    }
}
