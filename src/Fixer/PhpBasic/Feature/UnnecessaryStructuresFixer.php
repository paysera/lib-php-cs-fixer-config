<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class UnnecessaryStructuresFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    const CONVENTION = 'PhpBasic convention 3.10: We avoid unnecessary structures';

    /**
     * @var array
     */
    private $conditionalStatements;

    public function __construct()
    {
        parent::__construct();
        $this->conditionalStatements = [
            T_IF,
            T_ELSEIF,
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We avoid unnecessary structures.
            ',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        public function sampleFunction()
                        {
                            if ($first) {
                                if ($second) {
                                    do();
                                }
                            }
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_unnecessary_structures';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound($this->conditionalStatements);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
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
            $parentStartCurlyBraceIndex
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
                        $curlyBraceStartIndex
                    );
                    if ($curlyBraceEndIndex === $childEndCurlyBraceIndex) {
                        $this->insertComment(
                            $tokens,
                            $tokens[$conditionalStatementIndex]->getContent(),
                            $curlyBraceStartIndex
                        );
                        break;
                    }
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @return int|null
     */
    private function getStatementCurlyBraceStart(Tokens $tokens, $key)
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

    /**
     * @param Tokens $tokens
     * @param string $conditionalStatement
     * @param int $insertIndex
     */
    private function insertComment(Tokens $tokens, $conditionalStatement, $insertIndex)
    {
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt(
                $insertIndex + 1,
                new Token([T_COMMENT, '// TODO: "' . $conditionalStatement . '" - ' . self::CONVENTION])
            );
            $tokens->insertAt($insertIndex + 1, new Token([T_WHITESPACE, ' ']));
        }
    }
}
