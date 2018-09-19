<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment;

use SplFileInfo;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;

final class PhpDocNecessityFixer extends AbstractFixer
{
    public function getDefinition()
    {
        return new FixerDefinition(
            '
            If phpdoc comment doesn\'t reflect any used parameters, return types
            or exceptions being thrown, we remove it.
            ',
            [
                new CodeSample(
                    '<?php
                    
                        class Sample
                        {
                            /** 
                             * @var string
                             */
                            private $arg;
                            
                            /**
                             * Constructs class
                             */
                            public function __construct(string $arg)
                            {
                                $this->arg = $arg;
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_comment_php_doc_necessity';
    }

    public function getPriority()
    {
        return -48;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $docBlock = new DocBlock($token->getContent());

            foreach ($docBlock->getAnnotations() as $annotation) {
                if ($annotation->getTag()->valid()) {
                    continue 2;
                }
            }

            if ($this->isClassAnnotatedBlock($token)) {
                continue;
            }

            if ($this->validatePassedArguments($tokens, $key)) {
                continue;
            }

            $tokens->removeTrailingWhitespace($key);
            $tokens->clearTokenAndMergeSurroundingWhitespace($key);
        }
    }

    private function isClassAnnotatedBlock(Token $token): bool
    {
        return preg_match(
            '/(?<=@)\S+(?=\()/',
            $token->getContent()
        );
    }

    private function validatePassedArguments(Tokens $tokens, int $key): bool
    {
        $tokenKey = $tokens->getNextMeaningfulToken($key);

        if ($tokens[$tokenKey]->isGivenKind(T_CLASS)) {
            return false;
        }

        do {
            $tokenKey = $tokens->getNextMeaningfulToken($tokenKey);
        } while (!$tokens[$tokenKey]->isGivenKind([T_FUNCTION, T_VARIABLE, T_CLASS]));

        if ($tokens[$tokenKey]->isGivenKind([T_VARIABLE, T_CLASS])) {
            return false;
        }

        $functionNameIndex = $tokens->getNextMeaningfulToken($tokenKey);

        $parenthesisStartIndex = $tokens->getNextMeaningfulToken($functionNameIndex);
        $parenthesisEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parenthesisStartIndex);

        $argumentsAnalyzer = new ArgumentsAnalyzer();

        return $argumentsAnalyzer->countArguments($tokens, $parenthesisStartIndex, $parenthesisEndIndex) === 0;
    }
}
