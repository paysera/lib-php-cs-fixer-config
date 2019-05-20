<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class UnnecessaryVariablesFixer extends AbstractFixer
{
    const DEFAULT_CHARACTER_LENGTH = 45;
    const USED_COUNT = 2;
    const RETURN_COUNT = 1;
    const VALID_VARIABLE_LENGTH = 8;

    public function getDefinition()
    {
        return new FixerDefinition(
            'We avoid unnecessary variables. Risky for possible incompatibility',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            private function getSomething()
                            {
                                $a = get();
                                return $a;
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_unnecessary_variables';
    }

    public function isRisky()
    {
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_VARIABLE) && $tokens->isTokenKindFound(T_RETURN);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $searchFrom = 0;
        do {
            $indexes = $tokens->findSequence([[T_RETURN], [T_VARIABLE], ';'], $searchFrom);
            if ($indexes === null) {
                return;
            }
            $returnIndex = key($indexes);
            end($indexes);
            $searchFrom = $endToken = key($indexes);

            $assignmentColon = $tokens->getPrevMeaningfulToken($returnIndex);
            if ($assignmentColon === null || !$tokens[$assignmentColon]->equals(';')) {
                continue;
            }

            $startOfBlock = $tokens->getPrevTokenOfKind($assignmentColon, ['{', ';']);

            $variableIndex = $tokens->getNextMeaningfulToken($startOfBlock);
            $equalsIndex = $tokens->getNextMeaningfulToken($variableIndex);
            if (
                $tokens[$variableIndex]->isGivenKind(T_VARIABLE)
                && $tokens[$equalsIndex]->equals('=')
            ) {
                $tokens->overrideRange($variableIndex, $endToken, $this->getTokenRange(
                    $tokens,
                    [new Token([T_RETURN, 'return'])],
                    $equalsIndex + 1,
                    $assignmentColon
                ));
            }
        } while (true);
    }

    private function getTokenRange(Tokens $tokens, array $prefix, $start, $end)
    {
        $result = $prefix;
        for ($i = $start; $i <= $end; $i++) {
            $result[] = $tokens[$i];
        }
        return $result;
    }
}
