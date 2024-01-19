<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixtures;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class ReplaceContentsFixer extends AbstractFixer
{
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replace content fixer for testing.',
            [
                new CodeSample(<<<'PHP'
<?php

echo 'test';

PHP
                ),
            ],
            null,
            null,
            null,
            'Paysera recommendation.'
        );
    }

    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    public function isRisky()
    {
        return true;
    }

    public function getName()
    {
        return 'my_test_fixer';
    }

    public function getPriority()
    {
        return 123;
    }

    public function supports(SplFileInfo $file)
    {
        return true;
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $tokens->overrideRange(
            0,
            count($tokens) - 1,
            [
                new Token([T_OPEN_TAG, '<?php ']),
                new Token([T_COMMENT, '// replaced']),
            ]
        );
    }
}
