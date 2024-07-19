<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class CallingParentConstructorFixer extends AbstractFixer
{
    public const PARENT = 'parent';
    public const CONSTRUCT = '__construct';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'If we need to call parent constructor, we do it as first statement in constructor.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample extends ParentClass
{
    public function __construct($arg1, $arg2)
    {
        $this->setArg2($arg2);
        parent::__construct($arg1);
    }
}

PHP,
                ),
            ],
            null,
            null,
            null,
            'Paysera recommendation.',
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_feature_calling_parent_constructor';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if (
                $token->isGivenKind(T_STRING)
                && $token->getContent() === self::CONSTRUCT
                && $tokens[$tokens->getPrevMeaningfulToken($key)]->isGivenKind(T_FUNCTION)
            ) {
                $startIndex = $tokens->getNextTokenOfKind($key, ['{']);
                $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startIndex);
                $this->fixParentConstructPosition($tokens, $startIndex, $endIndex);
            }
        }
    }

    private function fixParentConstructPosition(Tokens $tokens, int $startIndex, int $endIndex)
    {
        $thisBeforeParentConstructor = false;
        for ($i = $startIndex; $i < $endIndex; $i++) {
            if ($tokens[$i]->getContent() === '$this') {
                $thisBeforeParentConstructor = true;
            }

            if (
                $tokens[$i]->getContent() === self::PARENT
                && $tokens[$i + 1]->isGivenKind(T_DOUBLE_COLON)
                && $tokens[$i + 2]->getContent() === self::CONSTRUCT
            ) {
                if (!$thisBeforeParentConstructor) {
                    return;
                }

                $parentEndIndex = $tokens->getNextTokenOfKind($i, [';']);
                $parentStatement = $this->getParentStatement($tokens, $i, $parentEndIndex);

                $tokens->overrideRange($startIndex + 1, $startIndex, $parentStatement);
                $oldIndexStart = $i + count($parentStatement) - 1;
                $oldIndexEnd = $oldIndexStart + count($parentStatement) - 1;
                $tokens->clearRange($oldIndexStart, $oldIndexEnd);
                return;
            }
        }
    }

    private function getParentStatement(Tokens $tokens, int $statementStartIndex, int $statementEndIndex): array
    {
        $argumentTokens = [];
        for ($i = $statementStartIndex - 1; $i <= $statementEndIndex; $i++) {
            $argumentTokens[] = $tokens[$i];
        }
        return $argumentTokens;
    }
}
