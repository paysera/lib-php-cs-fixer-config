<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class CallingParentConstructorFixer extends AbstractFixer
{
    const PARENT = 'parent';
    const CONSTRUCT = '__construct';

    public function getDefinition()
    {
        return new FixerDefinition(
            'If we need to call parent constructor, we do it as first statement in constructor.',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            public function __construct($arg1, $arg2)
                            {
                                $this->setArg2($arg2);
                                parent::__construct($arg1);
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_calling_parent_constructor';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function isRisky()
    {
        return true;
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
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

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     * @param int $endIndex
     */
    private function fixParentConstructPosition(Tokens $tokens, $startIndex, $endIndex)
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

    /**
     * @param Tokens $tokens
     * @param int $statementStartIndex
     * @param int $statementEndIndex
     * @return array
     */
    private function getParentStatement(Tokens $tokens, $statementStartIndex, $statementEndIndex)
    {
        $argumentTokens = [];
        for ($i = $statementStartIndex - 1; $i <= $statementEndIndex; $i++) {
            $argumentTokens[] = $tokens[$i];
        }
        return $argumentTokens;
    }
}
