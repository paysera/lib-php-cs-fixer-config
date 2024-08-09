<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixtures;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

class ReplaceContentsFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Replace content fixer for testing.',
            [
                new CodeSample(
                    <<<'PHP'
<?php

echo 'test';

PHP,
                ),
            ],
            null,
            'Paysera recommendation.',
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return true;
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return 'my_test_fixer';
    }

    public function getPriority(): int
    {
        return 123;
    }

    public function supports(SplFileInfo $file): bool
    {
        return true;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $tokens->overrideRange(
            0,
            count($tokens) - 1,
            [
                new Token([T_OPEN_TAG, '<?php ']),
                new Token([T_COMMENT, '// replaced']),
            ],
        );
    }
}
