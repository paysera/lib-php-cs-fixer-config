<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;

final class FunctionCountFixer extends AbstractFixer
{
    const SIZEOF = 'sizeof';
    const COUNT = 'count';

    public function getDefinition()
    {
        return new FixerDefinition(
            'We use count instead of sizeof.',
            [
                new CodeSample('
                <?php
                class Sample
                {
                    public function someFunction()
                    {
                        return sizeof([1, 2, 3, 4, 5]);
                        in_array(
                            $something,
                            [
                                sizeof([]),
                                count([]),
                                3,
                                4,
                                5
                            ],
                            $argument
                        );
                    }
                }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_function_count';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
                && $token->getContent() === self::SIZEOF
            ) {
                $token->setContent(self::COUNT);
            }
        }
    }
}
