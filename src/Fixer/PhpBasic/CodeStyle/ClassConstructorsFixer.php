<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class ClassConstructorsFixer extends AbstractFixer
{
    public function getDefinition()
    {
        return new FixerDefinition(
            'We always add () when constructing class, even if constructor takes no arguments',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        public function sampleFunction()
                        {
                            $value = new Sample;
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_class_constructors';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_NEW);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_NEW)) {
                continue;
            }

            // Check if it's object namespace
            $index = $tokens->getNextNonWhitespace($key);
            if ($tokens[$index]->isGivenKind(T_STRING)
                || $tokens[$index]->isGivenKind(T_NS_SEPARATOR)
            ) {
                // Skip all namespace
                while ($tokens[$index]->isGivenKind([T_STRING, T_NS_SEPARATOR])) {
                    $index++;
                }

                // Check for whitespace, example: \Exception ()
                if ($tokens[$index]->isWhitespace()) {
                    $index = $tokens->getNextMeaningfulToken($index);
                }

                if (!$tokens[$index]->equals('(')) {
                    $tokens->insertAt($index, new Token(')'));
                    $tokens->insertAt($index, new Token('('));
                }
            }
        }
    }
}
