<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class UnnecessaryStructuresFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public const CONVENTION = 'PhpBasic convention 3.10: We avoid unnecessary structures';

    private array $conditionalStatements;

    public function __construct()
    {
        parent::__construct();

        $this->conditionalStatements = [
            T_IF,
            T_ELSEIF,
        ];
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'We avoid unnecessary structures.',
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    public function sampleFunction()
    {
        if ($first) {
            if ($second) {
                doSomething();
            }
        }
    }
}

PHP,
                ),
            ],
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_feature_unnecessary_structures';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound($this->conditionalStatements);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if (!$tokens[$key]->isGivenKind($this->conditionalStatements)) {
                continue;
            }

            $curlyBraceStartIndex = $this->getStatementCurlyBraceStart($tokens, $key);
            if ($curlyBraceStartIndex !== null) {
                $this->checkUnnecessaryStructures($tokens, $curlyBraceStartIndex);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $parentStartCurlyBraceIndex
     */
    private function checkUnnecessaryStructures(Tokens $tokens, $parentStartCurlyBraceIndex)
    {
        $parentEndCurlyBraceIndex = $tokens->findBlockEnd(
            Tokens::BLOCK_TYPE_CURLY_BRACE,
            $parentStartCurlyBraceIndex,
        );

        $childEndCurlyBraceIndex = $tokens->getPrevMeaningfulToken($parentEndCurlyBraceIndex);

        if ($tokens[$childEndCurlyBraceIndex]->equals('}')) {
            for ($i = $parentStartCurlyBraceIndex; $i < $parentEndCurlyBraceIndex; $i++) {
                $conditionalStatementIndex = $tokens->getNextMeaningfulToken($i);
                if (!$tokens[$conditionalStatementIndex]->isGivenKind($this->conditionalStatements)) {
                    break;
                }
                $curlyBraceStartIndex = $this->getStatementCurlyBraceStart($tokens, $conditionalStatementIndex);
                if ($curlyBraceStartIndex !== null) {
                    $curlyBraceEndIndex = $tokens->findBlockEnd(
                        Tokens::BLOCK_TYPE_CURLY_BRACE,
                        $curlyBraceStartIndex,
                    );
                    if ($curlyBraceEndIndex === $childEndCurlyBraceIndex) {
                        $this->insertComment(
                            $tokens,
                            $tokens[$conditionalStatementIndex]->getContent(),
                            $curlyBraceStartIndex,
                        );
                        break;
                    }
                }
            }
        }
    }

    private function getStatementCurlyBraceStart(Tokens $tokens, int $key): ?int
    {
        $startParenthesesIndex = $tokens->getNextMeaningfulToken($key);
        if (!$tokens[$startParenthesesIndex]->equals('(')) {
            return null;
        }

        $endParenthesesIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startParenthesesIndex);
        $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($endParenthesesIndex);
        if (!$tokens[$curlyBraceStartIndex]->equals('{')) {
            return null;
        }

        return $curlyBraceStartIndex;
    }

    private function insertComment(Tokens $tokens, string $conditionalStatement, int $insertIndex)
    {
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertSlices([
                $insertIndex + 1 => [
                    new Token([T_WHITESPACE, ' ']),
                    new Token([T_COMMENT, '// TODO: "' . $conditionalStatement . '" - ' . self::CONVENTION]),
                ],
            ]);
        }
    }
}
