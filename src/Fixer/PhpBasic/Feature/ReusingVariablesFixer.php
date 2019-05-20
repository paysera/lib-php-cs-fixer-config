<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ReusingVariablesFixer extends AbstractFixer
{
    const CONVENTION = 'PhpBasic convention 3.9: We do not change argument type or value';

    private $assignmentOperators;
    private $typeCasters;

    public function __construct()
    {
        parent::__construct();
        $this->assignmentOperators = [
            T_CONCAT_EQUAL,
            T_AND_EQUAL,
            T_DIV_EQUAL,
            T_MINUS_EQUAL,
            T_MOD_EQUAL,
            T_MUL_EQUAL,
            T_OR_EQUAL,
            T_PLUS_EQUAL,
            T_SL_EQUAL,
            T_SR_EQUAL,
            T_XOR_EQUAL,
        ];
        $this->typeCasters = [
            T_ARRAY_CAST,
            T_BOOL_CAST,
            T_DOUBLE_CAST,
            T_INT_CAST,
            T_OBJECT_CAST,
            T_STRING_CAST,
            T_UNSET_CAST,
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We do not set value to variable passed as an argument.
            We do not change the type of the variable.
            ',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        public function thisIsWrong($number, $text, Request $request)
                        {
                            $number = (int)$number;  // Illegal: we 1) change argument value 2) change it\'s type
                            $text .= \' \';  // Illegal: we change argument value
                            $document = $request->get(\'documentId\');
                            $document = $this->repository->find($document); // Illegal: we change variable\'s type
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_reusing_variables';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_VARIABLE);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }

            $functionIndex = $tokens->getPrevMeaningfulToken($key);
            $parenthesesStartIndex = $tokens->getNextMeaningfulToken($key);
            if (
                !$tokens[$parenthesesStartIndex]->equals('(')
                && !$tokens[$functionIndex]->isGivenKind(T_FUNCTION)
            ) {
                continue;
            }
            $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parenthesesStartIndex);

            $methodArguments = $this->getMethodArguments($tokens, $parenthesesStartIndex, $parenthesesEndIndex);
            if (count($methodArguments) === 0) {
                continue;
            }

            $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);
            if (!$tokens[$curlyBraceStartIndex]->equals('{')) {
                continue;
            }
            $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);

            $this->validateArgumentUsage($tokens, $methodArguments, $curlyBraceStartIndex, $curlyBraceEndIndex);
        }
    }

    /**
     * @param Tokens $tokens
     * @param array $methodArguments
     * @param int $startIndex
     * @param int $endIndex
     */
    private function validateArgumentUsage(Tokens $tokens, $methodArguments, $startIndex, $endIndex)
    {
        $localVariables = [];

        for ($i = $startIndex; $i < $endIndex; $i++) {
            if (!$tokens[$i]->isGivenKind(T_VARIABLE)) {
                continue;
            }
            $variableContent = $tokens[$i]->getContent();
            $equalTokenIndex = $tokens->getNextMeaningfulToken($i);
            if (
                in_array($variableContent, $methodArguments, true)
                && (
                    $tokens[$equalTokenIndex]->isGivenKind($this->assignmentOperators)
                    || $tokens[$equalTokenIndex]->equals('=')
                )
            ) {
                $this->insertComment($tokens, $tokens->getNextTokenOfKind($i, [';']), $variableContent);
                continue;
            }

            $typeCastIndex = $tokens->getNextMeaningfulToken($equalTokenIndex);
            if (
                in_array($variableContent, $localVariables, true)
                && $tokens[$typeCastIndex]->isGivenKind($this->typeCasters)
            ) {
                $this->insertComment($tokens, $tokens->getNextTokenOfKind($i, [';']), $variableContent);
                continue;
            }

            if (!in_array($variableContent, $methodArguments, true)) {
                $localVariables[] = $variableContent;
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     * @param int $endIndex
     * @return array
     */
    private function getMethodArguments(Tokens $tokens, $startIndex, $endIndex)
    {
        $methodArguments = [];
        for ($i = $startIndex; $i < $endIndex; $i++) {
            if ($tokens[$i]->isGivenKind(T_VARIABLE)) {
                $methodArguments[] = $tokens[$i]->getContent();
            }
        }
        return $methodArguments;
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param string $variableName
     */
    private function insertComment(Tokens $tokens, $insertIndex, $variableName)
    {
        $comment = '// TODO: "' . $variableName . '" - ' . self::CONVENTION;
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt($insertIndex + 1, new Token([T_COMMENT, $comment]));
            $tokens->insertAt($insertIndex + 1, new Token([T_WHITESPACE, ' ']));
        }
    }
}
