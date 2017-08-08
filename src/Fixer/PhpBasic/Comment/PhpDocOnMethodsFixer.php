<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

final class PhpDocOnMethodsFixer extends AbstractFixer
{
    /**
     * @var array
     */
    private $typeHints = [
        CT::T_ARRAY_TYPEHINT,
    ];

    /**
     * @var array
     */
    private $annotationExclusions = [
        '@return',
        '@throws',
        '@see',
    ];

    public function getDefinition()
    {
        return new FixerDefinition('
            We put PhpDoc comment on all methods with exception of constructors (can be skipped in some cases).
            
            We put PhpDoc comment on constructors when IDE (for example PhpStorm) cannot guess classes of attributes,
            return type etc. It’s optional otherwise.
            For example, if we inject some scalar type, we must put PhpDoc comment.
            ',
            [
                new CodeSample('
                <?php
                namespace Some\Entity;
                    class Sample
                    {
                        /**
                         * @param SomeEntity $something
                         */
                        public function sampleFunction(SomeEntity $something)
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
        return 'Paysera/php_basic_comment_php_doc_on_methods';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $tokens->getPrevNonWhitespace($functionTokenIndex);
            if ($token->isGivenKind(T_STRING) && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
                && $tokens[$visibilityTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            ) {
                $index = $tokens->getPrevNonWhitespace($visibilityTokenIndex);
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $docBlockIndex = null;
                if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $index;
                } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $tokens->getPrevNonWhitespace($index);
                }

                if ($docBlockIndex !== null
                    && !preg_match('#@inheritdoc#', strtolower($tokens[$docBlockIndex]->getContent()))
                ) {
                    $this->validateDocBlock($tokens, $docBlockIndex, $key + 1, $parenthesesEndIndex);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param int $parenthesesStartIndex
     * @param int $parenthesesEndIndex
     */
    private function validateDocBlock(Tokens $tokens, $docBlockIndex, $parenthesesStartIndex, $parenthesesEndIndex)
    {
        $variablesWithoutTypeHint = 0;
        $variableCount = 0;
        for ($i = $parenthesesStartIndex; $i < $parenthesesEndIndex; ++$i) {
            if ($tokens[$i]->isGivenKind(T_VARIABLE)) {
                $variableCount++;
                $previousTokenIndex = $tokens->getPrevMeaningfulToken($i);
                if (!$tokens[$previousTokenIndex]->isGivenKind(T_STRING)
                    && !$tokens[$previousTokenIndex]->isGivenKind($this->typeHints)
                ) {
                    $variablesWithoutTypeHint++;
                }
            }
        }

        if ($variableCount > 0 && $variablesWithoutTypeHint === 0 && $docBlockIndex !== null) {
            $docBlockContent = $tokens[$docBlockIndex]->getContent();
            $docBlock = new DocBlock($docBlockContent);
            $lineCount = count($docBlock->getLines());
            $paramAnnotations = $docBlock->getAnnotationsOfType('param');

            if (preg_match('#\/\*\*\n#', $docBlock->getLine(0)) === 1
                && preg_match('#\*\/#', trim($docBlock->getLine($lineCount - 1))) === 1
                && count($paramAnnotations) === $lineCount - 2
                && $tokens[$docBlockIndex + 1]->isWhitespace()
            ) {
                $tokens->clearRange($docBlockIndex, $docBlockIndex + 1);
            } elseif (preg_match('#' . implode('|', $this->annotationExclusions) . '#', $docBlockContent) !== 1) {
                foreach ($paramAnnotations as $annotation) {
                    $annotation->remove();
                }
                $tokens[$docBlockIndex]->setContent($docBlock->getContent());
            }
        }
    }
}
