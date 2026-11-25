<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Analyzer\WhitespacesAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class OneLineChainedMethodCallsFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    private const CHAIN_TOKEN_KINDS = [T_VARIABLE, T_STRING, T_STATIC, T_DOUBLE_COLON, T_NS_SEPARATOR];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'A chain of two or more method calls written on one line is split: every call after the first one
            comes on its own indented line, and the semicolon on a separate line.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
    class Sample
    {
        public function sampleFunction()
        {
            $mock->expects($this->once())->method('get')->willReturn(1);
        }
    }

PHP,
                ),
            ],
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_code_style_one_line_chained_method_calls';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_OBJECT_OPERATOR);
    }

    public function getPriority(): int
    {
        return 11;
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach (array_reverse($this->findStatements($tokens)) as [$startIndex, $endIndex]) {
            $this->fixStatement($tokens, $startIndex, $endIndex);
        }
    }

    private function findStatements(Tokens $tokens): array
    {
        $statements = [];
        $openBlockIsCurlyBrace = [];
        $startIndex = null;

        for ($index = 0; $index < $tokens->count(); $index++) {
            $token = $tokens[$index];

            $blockType = Tokens::detectBlockType($token);
            if ($blockType !== null && $blockType['isStart']) {
                $openBlockIsCurlyBrace[] = $token->equals('{');
            } elseif ($blockType !== null) {
                array_pop($openBlockIsCurlyBrace);
            }

            if ($token->equalsAny([';', '{', '}']) || $token->isGivenKind(T_OPEN_TAG)) {
                $isStatementLevel = $openBlockIsCurlyBrace === [] || end($openBlockIsCurlyBrace);
                if ($token->equals(';') && $isStatementLevel && $startIndex !== null) {
                    $statements[] = [$startIndex, $index];
                }
                $startIndex = null;
            } elseif ($startIndex === null && !$token->isWhitespace()) {
                $startIndex = $index;
            }
        }

        return $statements;
    }

    private function fixStatement(Tokens $tokens, int $startIndex, int $endIndex): void
    {
        if (!$this->isOnItsOwnLine($tokens, $startIndex, $endIndex)) {
            return;
        }

        $chainStartIndex = $this->findChainStart($tokens, $startIndex, $endIndex);
        $operatorIndexes = $chainStartIndex !== null
            ? $this->findChainOperators($tokens, $chainStartIndex, $endIndex)
            : null;
        if ($operatorIndexes === null) {
            return;
        }

        $callOperatorIndexes = array_filter(
            $operatorIndexes,
            fn (int $operatorIndex) => $this->isMethodCall($tokens, $operatorIndex),
        );
        if (count($callOperatorIndexes) < 2) {
            return;
        }

        $lineIndent = $this->whitespacesConfig->getLineEnding() . WhitespacesAnalyzer::detectIndent($tokens, $startIndex);
        $tokens->ensureWhitespaceAtIndex($endIndex - 1, 1, $lineIndent);
        foreach (array_reverse($operatorIndexes) as $operatorIndex) {
            if ($tokens[$tokens->getPrevMeaningfulToken($operatorIndex)]->equals(')')) {
                $tokens->ensureWhitespaceAtIndex(
                    $operatorIndex - 1,
                    1,
                    $lineIndent . $this->whitespacesConfig->getIndent(),
                );
            }
        }
    }

    private function isOnItsOwnLine(Tokens $tokens, int $startIndex, int $endIndex): bool
    {
        for ($index = $startIndex; $index <= $endIndex; $index++) {
            if (str_contains($tokens[$index]->getContent(), "\n")) {
                return false;
            }
        }

        return
            str_contains($tokens[$startIndex - 1]->getContent(), "\n")
            && (!isset($tokens[$endIndex + 1]) || str_contains($tokens[$endIndex + 1]->getContent(), "\n"))
        ;
    }

    private function findChainStart(Tokens $tokens, int $startIndex, int $endIndex): ?int
    {
        if ($tokens[$startIndex]->isGivenKind(T_RETURN)) {
            return $tokens->getNextMeaningfulToken($startIndex);
        }

        for ($index = $startIndex; $index < $endIndex; $index++) {
            if ($tokens[$index]->equals('=')) {
                return $this->findChainOperators($tokens, $startIndex, $index) !== null
                    ? $tokens->getNextMeaningfulToken($index)
                    : null;
            }

            $blockType = Tokens::detectBlockType($tokens[$index]);
            if ($blockType !== null && $blockType['isStart']) {
                $index = $tokens->findBlockEnd($blockType['type'], $index);
            }
        }

        return $startIndex;
    }

    private function findChainOperators(Tokens $tokens, int $startIndex, int $endIndex): ?array
    {
        $operatorIndexes = [];
        for ($index = $startIndex; $index < $endIndex; $index++) {
            $token = $tokens[$index];

            $blockType = Tokens::detectBlockType($token);
            if ($blockType !== null && $blockType['isStart']) {
                $index = $tokens->findBlockEnd($blockType['type'], $index);
            } elseif ($token->isGivenKind(T_OBJECT_OPERATOR)) {
                $operatorIndexes[] = $index;
            } elseif (!$token->isWhitespace() && !$token->isGivenKind(self::CHAIN_TOKEN_KINDS)) {
                return null;
            }
        }

        return $operatorIndexes;
    }

    private function isMethodCall(Tokens $tokens, int $operatorIndex): bool
    {
        $nameIndex = $tokens->getNextMeaningfulToken($operatorIndex);

        return
            $tokens[$nameIndex]->isGivenKind(T_STRING)
            && $tokens[$tokens->getNextMeaningfulToken($nameIndex)]->equals('(')
        ;
    }
}
