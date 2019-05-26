<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixtures;

use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class ReplaceContentsFixer implements FixerInterface
{
    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    public function isRisky()
    {
        return false;
    }

    public function fix(SplFileInfo $file, Tokens $tokens)
    {
        $tokens->overrideRange(0, count($tokens) - 1, [
            new Token([T_OPEN_TAG, '<?php ']),
            new Token([T_COMMENT, '// replaced']),
        ]);
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
}
