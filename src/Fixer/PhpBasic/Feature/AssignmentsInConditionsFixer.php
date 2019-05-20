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

final class AssignmentsInConditionsFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    const CONVENTION = 'PhpBasic convention 3.7: We do not use assignments inside conditional statements';

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
            We do not use assignments inside conditional statements.
            Exception: in a while loop condition.
            ',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        public function sampleFunction()
                        {
                            if (($b = $a->get()) !== null && ($c = $b->get()) !== null) {
                                $c->do();
                            }
                            if ($project = $this->findProject()) {
                             
                            }
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_assignments_in_conditions';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound($this->conditionalStatements);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind($this->conditionalStatements)) {
                continue;
            }

            $startParenthesesIndex = $tokens->getNextMeaningfulToken($key);
            if (!$tokens[$startParenthesesIndex]->equals('(')) {
                continue;
            }

            $this->checkConditionalStatement($tokens, $startParenthesesIndex);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     */
    private function checkConditionalStatement(Tokens $tokens, $startIndex)
    {
        $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);
        for ($i = $startIndex; $i < $endIndex; $i++) {
            if ($tokens[$i]->equals('=')) {
                $this->insertComment(
                    $tokens,
                    $tokens->getPrevMeaningfulToken($i),
                    $tokens->getNextTokenOfKind($endIndex, ['{'])
                );
                break;
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $variableIndex
     * @param int $insertIndex
     */
    private function insertComment(Tokens $tokens, $variableIndex, $insertIndex)
    {
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt(
                $insertIndex + 1,
                new Token([T_COMMENT, '// TODO: "' . $tokens[$variableIndex]->getContent() . '" - ' . self::CONVENTION])
            );
            $tokens->insertAt($insertIndex + 1, new Token([T_WHITESPACE, ' ']));
        }
    }
}
