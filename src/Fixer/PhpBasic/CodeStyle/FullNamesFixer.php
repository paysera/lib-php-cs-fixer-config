<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class FullNamesFixer extends AbstractFixer
{
    const MINIMUM_NAMESPACE_CHARACTER_LENGTH = 4;

    /**
     * @var array
     */
    private $scalarTypes;

    public function __construct()
    {
        parent::__construct();
        $this->scalarTypes = [
            'array',
            'string',
            'int',
            'float',
            'bool',
            'callable',
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We use full names, not abbreviations: $entityManager instead of $em, $exception instead of $e.
            Risky for possible local variable duplicate renaming
            ',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        public function sampleFunction(SomeProvider $sp)
                        {
                            $a = $sp;
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_full_names';
    }

    public function isRisky()
    {
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_VARIABLE);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $invalidVariableNames = [];
        // First cycle for collecting Invalid Variable Names
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_VARIABLE)) {
                continue;
            }

            $variableContent = $token->getContent();
            $namespaceName = $this->getNamespaceName($tokens, $key);
            $previousTokenIndex = $tokens->getPrevMeaningfulToken($key);
            if (
                $namespaceName !== null
                && (
                    strlen($variableContent) < self::MINIMUM_NAMESPACE_CHARACTER_LENGTH
                    || $this->isVariableTruncated($variableContent, $namespaceName)
                )
                && !$tokens[$previousTokenIndex]->isGivenKind([T_PRIVATE, T_PUBLIC, T_PROTECTED, T_STATIC])
                && !$tokens[$previousTokenIndex]->equals([T_OBJECT_OPERATOR])
                && !in_array($tokens[$previousTokenIndex]->getContent(), $this->scalarTypes, true)
            ) {
                $invalidVariableNames[$namespaceName] = $variableContent;
            }
        }

        // Second cycle for renaming
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_DOC_COMMENT)) {
                $this->fixDocBlockVariableNames($tokens, $key, $invalidVariableNames);
            }

            if (!$token->isGivenKind(T_VARIABLE)) {
                continue;
            }

            $variableContent = $token->getContent();
            $previousTokenIndex = $tokens->getPrevMeaningfulToken($key);
            $namespaceName = array_search($variableContent, $invalidVariableNames, true);
            if (
                $namespaceName
                && !$tokens[$previousTokenIndex]->isGivenKind([T_PRIVATE, T_PUBLIC, T_PROTECTED, T_STATIC])
                && !$tokens[$previousTokenIndex]->equals([T_OBJECT_OPERATOR])
            ) {
                $namespaceName = preg_replace('#_#', '', $namespaceName);
                $token->setContent('$' . lcfirst($namespaceName));
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param array $invalidVariableNames
     */
    private function fixDocBlockVariableNames(Tokens $tokens, $docBlockIndex, $invalidVariableNames)
    {
        $docBlockContent = $tokens[$docBlockIndex]->getContent();
        $replacement = $docBlockContent;
        foreach ($invalidVariableNames as $key => $invalidVariableName) {
            $pattern = '#' . $key . '\s\\' . $invalidVariableName . '#';
            if (preg_match($pattern, $docBlockContent)) {
                $replacement = preg_replace($pattern, $key . ' $' . lcfirst($key), $replacement);
            }
        }
        $tokens[$docBlockIndex]->setContent($replacement);
    }

    /**
     * @param string $variableContent
     * @param string $namespaceName
     * @return bool
     */
    private function isVariableTruncated($variableContent, $namespaceName)
    {
        preg_match_all('/[A-Z]/', $namespaceName, $matches);
        if (isset($matches[0])) {
            $namespaceLetters = implode('', $matches[0]);
            $namespaceLetters = strtolower($namespaceLetters);
            $variableLetters = preg_replace('#\$#', '', $variableContent);
            if (strtolower($namespaceLetters) === strtolower($variableLetters)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @return null|string
     */
    private function getNamespaceName(Tokens $tokens, $key)
    {
        $namespaceIndex = $tokens->getPrevMeaningfulToken($key);
        $nextTokenIndex = $tokens->getNextMeaningfulToken($key);
        if (
            $tokens[$namespaceIndex]->isGivenKind(T_STRING)
            && !$tokens[$nextTokenIndex]->equals('=')
        ) {
            return $tokens[$namespaceIndex]->getContent();
        }

        if (
            $tokens[$nextTokenIndex]->equals('=')
            && $tokens[$tokens->getNextMeaningfulToken($nextTokenIndex)]->isGivenKind(T_NEW)
        ) {
            $namespaceIndex = $tokens->getNextTokenOfKind($key, ['(', ';']) - 1;
            if ($tokens[$namespaceIndex]->isGivenKind(T_STRING)) {
                return $tokens[$namespaceIndex]->getContent();
            }
        }

        return null;
    }
}
