<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class FluidInterfaceFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    const NAMESPACE_ENTITY = 'Entity';
    const VARIABLE_THIS = '$this';

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            If method returns $this, we use @return $this that IDE could guess correct type
            if we use this method for objects of sub-classes.
            ',
            [
                new CodeSample('
                <?php
                namespace Some\Entity;
                    class Sample
                    {
                        /**
                         * @param int $something
                         */
                        public function sampleFunction($something)
                        {
                            return $this;
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_comment_fluid_interface';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_VARIABLE);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $namespace = $tokens->getPrevMeaningfulToken($tokens->getNextTokenOfKind($key, [';']));
            }

            if (!isset($namespace) || $tokens[$namespace]->getContent() !== self::NAMESPACE_ENTITY) {
                continue;
            }

            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $tokens->getPrevNonWhitespace($functionTokenIndex);
            if (
                $token->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
                && $tokens[$visibilityTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            ) {
                $index = $tokens->getPrevNonWhitespace($visibilityTokenIndex);
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);
                $docBlockIndex = null;
                if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $index;
                } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $tokens->getPrevNonWhitespace($index);
                }
                if ($docBlockIndex !== null && $tokens[$curlyBraceStartIndex]->equals('{')) {
                    $this->validateFluidInterface($tokens, $curlyBraceStartIndex, $docBlockIndex);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $curlyBraceStartIndex
     * @param int $docBlockIndex
     */
    private function validateFluidInterface(Tokens $tokens, $curlyBraceStartIndex, $docBlockIndex)
    {
        $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
        for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; $i++) {
            if ($tokens[$i]->isGivenKind(T_RETURN)) {
                $thisVariableIndex = $tokens->getNextMeaningfulToken($i);
                $semicolonIndex = $tokens->getNextMeaningfulToken($thisVariableIndex);
                if (
                    $tokens[$thisVariableIndex]->isGivenKind(T_VARIABLE)
                    && $tokens[$thisVariableIndex]->getContent() === self::VARIABLE_THIS
                    && $tokens[$semicolonIndex]->equals(';')
                ) {
                    $this->validateDocBlock($tokens, $docBlockIndex);
                    break;
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     */
    private function validateDocBlock(Tokens $tokens, $docBlockIndex)
    {
        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
        $returnsBlock = $docBlock->getAnnotationsOfType('return');

        if (!count($returnsBlock)) {
            $lines = $docBlock->getLines();
            $linesCount = count($lines);

            preg_match('/^(\s*).*$/', $lines[$linesCount - 1]->getContent(), $matches);
            $indent = $matches[1];

            $returnLine[] = new Line(sprintf(
                '%s* @return %s %s',
                $indent,
                self::VARIABLE_THIS,
                $this->whitespacesConfig->getLineEnding()
            ));

            array_splice($lines, $linesCount - 1, 0, $returnLine);
            $tokens[$docBlockIndex]->setContent(implode('', $lines));
        } elseif ($returnsBlock[0]->getTypes() !== self::VARIABLE_THIS) {
            $returnsBlock[0]->setTypes([self::VARIABLE_THIS]);
            $tokens[$docBlockIndex]->setContent($docBlock->getContent());
        }
    }
}
