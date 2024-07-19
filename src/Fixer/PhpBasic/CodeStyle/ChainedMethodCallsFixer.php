<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ChainedMethodCallsFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'When making chain method calls, we put semicolon on it’s own separate line,
            chained method calls are indented and comes in it’s own line.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
    class Sample
    {
        public function sampleFunction()
        {
            return $this->createQueryBuilder("a")->join("a.items", "i")->andWhere("i.param = :param")
                ->setParameter("param", $param)->getQuery()
                ->getResult();
        }
    }

PHP,
                ),
            ],
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_code_style_chained_method_calls';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_OBJECT_OPERATOR);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        for ($key = 0; $key < $tokens->count(); $key++) {
            if (!$tokens[$key]->isGivenKind(T_OBJECT_OPERATOR)) {
                continue;
            }

            $semicolonIndex = $tokens->getNextTokenOfKind($key, [';']);
            $indent = $this->checkForMethodSplits(
                $tokens,
                $key,
                $semicolonIndex,
            );

            if ($indent !== null) {
                $key = $this->validateMethodSplits($tokens, $key, $semicolonIndex, $indent);
            }
        }
    }

    private function validateMethodSplits(Tokens $tokens, int $startIndex, int $endIndex, string $indent): int
    {
        for ($i = $startIndex; $i < $endIndex; $i++) {
            if ($tokens[$i]->equals('(')) {
                $i = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $i);
            }

            if (
                $tokens[$i]->isGivenKind(T_OBJECT_OPERATOR)
                && $tokens[$i - 1]->equals(')')
            ) {
                $tokens->insertSlices([$i => [new Token([T_WHITESPACE, $indent])]]);
            }
        }

        if (!$tokens[$i - 1]->isWhitespace() && strpos($tokens[$i - 2]->getContent(), "\n") === false) {
            $tokens->insertSlices([
                $i => [
                    new Token([
                        T_WHITESPACE,
                        preg_replace(
                            '/' . preg_quote($this->whitespacesConfig->getIndent(), '/') . '/',
                            '',
                            $indent,
                            1,
                        ),
                    ])
                ]
            ]);
        }

        return $i;
    }

    private function checkForMethodSplits(Tokens $tokens, int $startIndex, int $endIndex): ?string
    {
        for ($i = $startIndex; $i < $endIndex; $i++) {
            if ($tokens[$i]->equals('(')) {
                $blockEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $i);
                $i = $blockEndIndex;
            }

            if (
                $tokens[$i]->isGivenKind(T_OBJECT_OPERATOR)
                && $tokens[$i - 1]->isWhitespace()
            ) {
                return $tokens[$i - 1]->getContent();
            }
        }
        return null;
    }
}
