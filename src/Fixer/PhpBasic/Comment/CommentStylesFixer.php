<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class CommentStylesFixer extends AbstractFixer
{
    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We use multi-line /** */ comments for method, property and class annotations.
            We use single-line /** @var Class $object */ annotation for local variables.
            We can use // single line comments in the code.
            We do not use /* */ or # comments at all.
            ',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        /** @var string $variable */
                        private $variable;
                        
                        /* some comment */
                        public function sampleFunction()
                        {
                            /**
                             * @var Sample $value
                             */
                            $value = new Sample;
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_comment_comment_styles';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_COMMENT, T_DOC_COMMENT]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (
                $token->isGivenKind(T_COMMENT)
                && isset($tokens[$key + 1])
                && $tokens[$key + 1]->isWhitespace()
                && strpos($tokens[$key + 1]->getContent(), "\n") !== false
            ) {
                $this->fixComments($tokens, $key);
            }

            $docBlockIndex = null;
            $curlyBraceStartIndex = null;

            // Get Class docBlock
            if ($docBlockIndex === null) {
                $docBlockIndex = $this->getClassDocBlockIndex($tokens, $key);
            }

            // Get property docBlock
            if ($docBlockIndex === null) {
                $docBlockIndex = $this->getPropertyDocBlockIndex($tokens, $key);
            }

            // Get function docBlock
            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $tokens->getPrevNonWhitespace($functionTokenIndex);
            if (
                $tokens[$key]->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
                && $tokens[$visibilityTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            ) {
                $index = $tokens->getPrevNonWhitespace($visibilityTokenIndex);
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);
                if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $index;
                } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $tokens->getPrevNonWhitespace($index);
                }
            }

            if ($docBlockIndex !== null && $tokens[$docBlockIndex + 1]->isWhitespace()) {
                $this->validateMultiLineDocBlock($tokens, $docBlockIndex);
            }

            if (isset($curlyBraceStartIndex) && $tokens[$curlyBraceStartIndex]->equals('{')) {
                $this->validateSingleLineDocBlocks($tokens, $curlyBraceStartIndex);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     */
    private function fixComments(Tokens $tokens, $key)
    {
        $commentContent = $tokens[$key]->getContent();
        if (preg_match('#\\\?\/\*[^*]#', $commentContent)) {
            if (strpos($commentContent, "\n")) {
                $this->fixMultiLineComment($tokens, $key, $commentContent);
            } else {
                preg_match('#\/\*(.*?)\*\/#', $commentContent, $match);
                if (isset($match[1])) {
                    $tokens[$key]->setContent('//' . $match[1]);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $commentIndex
     * @param int $commentContent
     */
    private function fixMultiLineComment(Tokens $tokens, $commentIndex, $commentContent)
    {
        $index = $commentIndex;
        $indent = $tokens[$commentIndex + 1]->getContent();
        preg_match('#\/\*(.*?)\*\/#s', $commentContent, $match);
        if (isset($match[1])) {
            $replacement = preg_replace('#\s\*\s#', ' ', $match[1]);
            $replacementArray = preg_split("#\n#", $replacement);
            $replacementArray = array_map('trim', $replacementArray);
            $replacementArray = array_filter($replacementArray);
            $replacementArray = array_values($replacementArray);
            foreach ($replacementArray as $key => &$line) {
                if ($key > 0) {
                    $tokens->insertAt(++$index, new Token([T_WHITESPACE, $indent]));
                }
                $tokens->insertAt(++$index, new Token([T_COMMENT, '// ' . $line]));
            }
            $tokens->clearRange($commentIndex, $commentIndex);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @return int|null
     */
    private function getPropertyDocBlockIndex(Tokens $tokens, $key)
    {
        if ($tokens[$key]->isGivenKind(T_VARIABLE)) {
            $previousTokenIndex = $tokens->getPrevNonWhitespace($key);
            $previousPreviousTokenIndex = $tokens->getPrevNonWhitespace($previousTokenIndex);
            if (
                $tokens[$previousTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                || (
                    $tokens[$previousTokenIndex]->isGivenKind(T_STATIC)
                    && $tokens[$previousPreviousTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                )
            ) {
                $index = $tokens->getPrevNonWhitespace($previousTokenIndex);
                if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                    return $index;
                } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
                    return $tokens->getPrevNonWhitespace($index);
                }
            }
        }
        return null;
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @return int|null
     */
    private function getClassDocBlockIndex(Tokens $tokens, $key)
    {
        if ($tokens[$key]->isGivenKind(Token::getClassyTokenKinds())) {
            $previousTokenIndex = $tokens->getPrevNonWhitespace($key);
            $previousPreviousTokenIndex = $tokens->getPrevNonWhitespace($previousTokenIndex);
            if ($tokens[$previousTokenIndex]->isGivenKind(T_DOC_COMMENT)) {
                return $previousTokenIndex;
            } elseif (
                isset($previousPreviousTokenIndex)
                && $tokens[$previousPreviousTokenIndex]->isGivenKind(T_DOC_COMMENT)
            ) {
                return $tokens->getPrevNonWhitespace($previousTokenIndex);
            }
        }
        return null;
    }

    private function validateMultiLineDocBlock(Tokens $tokens, $key)
    {
        $docBlockContent = $tokens[$key]->getContent();
        $indent = $tokens[$key + 1]->getContent();
        $indent = preg_replace('#\n#', ' ', $indent);

        $docBlock = new DocBlock($docBlockContent);
        $lines = $docBlock->getLines();
        $linesCount = count($lines);

        if ($linesCount === 1) {
            $this->convertToMultiLineDocBlock($tokens, $key, $docBlockContent, $indent);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $curlyBraceStartIndex
     */
    private function validateSingleLineDocBlocks(Tokens $tokens, $curlyBraceStartIndex)
    {
        $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
        for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; $i++) {
            $docBlockContent = $tokens[$i]->getContent();
            if (
                $tokens[$i]->isGivenKind(T_DOC_COMMENT)
                && strpos($docBlockContent, "\n")
                && strpos($docBlockContent, '@')
            ) {
                $this->convertToSingleLineDocBlock($tokens, $i, $docBlockContent);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param string $docBlockContent
     */
    private function convertToSingleLineDocBlock(Tokens $tokens, $key, $docBlockContent)
    {
        $replacement = preg_replace('#\s\*\s#', ' ', $docBlockContent);
        $replacement = preg_replace('#\s+#', ' ', $replacement);
        $tokens[$key]->setContent($replacement);
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param string $docBlockContent
     * @param string $indent
     */
    private function convertToMultiLineDocBlock(Tokens $tokens, $docBlockIndex, $docBlockContent, $indent)
    {
        preg_match('#\/\**(.*?)\*\/#', $docBlockContent, $match);
        if (isset($match[1])) {
            $innerContent = trim($match[1]);
            $innerLines = preg_split('#(?=@)#', $innerContent);
            $innerLines = array_filter($innerLines);
            $innerLines = array_map('trim', $innerLines);
            foreach ($innerLines as $key => &$line) {
                $line = $indent . '* ' . $line . "\n";
            }
            $docBlockLines[] = "/**\n";
            $docBlockLines = array_merge($docBlockLines, $innerLines);
            $docBlockLines[] = $indent . '*/';
            $tokens[$docBlockIndex]->setContent(implode('', $docBlockLines));
        }
    }
}
