<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class NamespacesAndUseStatementsFixer extends AbstractFixer
{
    /**
     * @var array
     */
    private $classNameExceptions = [
        'Exception',
        'Controller',
        'SplFileInfo',
    ];

    /**
     * @var array
     */
    private $docBlockAnnotations = [
        '@throws',
        '@return',
        '@returns',
        '@param',
        '@var',
    ];

    public function getDefinition()
    {
        return new FixerDefinition(
            'If class has a namespace, we use "use" statements instead of providing full namespace.
            This applies to php-doc comments, too.
            
            Does not process namespace without root. Example: Some\Entity\Operation.
            Risky, because of possible duplicate class name like: \Exception and \Some\Namespace\Http\Exception
            ',
            [
                new CodeSample('
                <?php
                
                namespace Some\Namespace;
                
                class Sample
                {
                    /**
                     * @var \Vendor\Namespace\Entity\Value $value
                     */
                    public function sampleFunction(\Vendor\Namespace\Entity\Value $value)
                    {
                        $sample = new \Some\ClassName\Sample();
                    }
                }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_namespaces_and_use_statements';
    }

    public function getPriority()
    {
        return 70;
    }

    public function isRisky()
    {
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_NAMESPACE);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $endOfUseStatements = [];

        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $namespaceIndex = $key;
            }

            if ($token->isGivenKind(T_USE)) {
                $endOfUseStatement = strtolower($tokens[$tokens->getNextTokenOfKind($key, [';']) - 1]->getContent());
                if (!in_array($endOfUseStatement, $endOfUseStatements, true)) {
                    $endOfUseStatements[] = $endOfUseStatement;
                }
            }

            if ($token->isGivenKind(T_DOC_COMMENT) && isset($namespaceIndex)) {
                $endOfUseStatements = $docBlockNamespace = $this->fixDocBlockContent(
                    $tokens,
                    $key,
                    $namespaceIndex,
                    $endOfUseStatements,
                    $this->classNameExceptions
                );
            }

            if (!$token->isGivenKind(T_NS_SEPARATOR)) {
                continue;
            }

            if (!$tokens[$key - 1]->isGivenKind(T_STRING)
                && !$tokens[$key - 2]->isGivenKind(T_USE)
                && isset($namespaceIndex)
            ) {
                $classNameEndIndex = $this->findNamespaceEndIndex($tokens, $key);

                // Saving that namespace as string
                $useStatementContent = '\\';
                for ($i = $key + 1; $i <= $classNameEndIndex; ++$i) {
                    $useStatementContent .= $tokens[$i]->getContent();
                }

                if (in_array($tokens[$classNameEndIndex]->getContent(), $this->classNameExceptions, true)) {
                    continue;
                }

                preg_match('#\\\?[\w\\\]*\\\(\w*)#', $useStatementContent, $endOfNamespace);
                if (!isset($endOfNamespace[1])) {
                    continue;
                }

                $inserted = false;
                if (!in_array(strtolower($endOfNamespace[1]), $endOfUseStatements, true)) {
                    $inserted = $this->insertUseStatement($tokens, $namespaceIndex, $useStatementContent);
                    $endOfUseStatements[] = strtolower($endOfNamespace[1]);
                }

                if (!$inserted) {
                    $tokens->clearRange($key, $classNameEndIndex - 1);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $key
     * @param int $namespaceIndex
     * @param array $endOfUseStatements
     * @param array $exceptionClassNames
     * @return array
     */
    private function fixDocBlockContent(
        Tokens $tokens,
        $key,
        $namespaceIndex,
        $endOfUseStatements,
        $exceptionClassNames
    ) {
        $content = $tokens[$key]->getContent();
        preg_match_all(
            '#(?<='. implode('|', $this->docBlockAnnotations) . ')\s(\\\[\w\\\]*\\\?(\w*))\s#',
            $content,
            $matches,
            PREG_SET_ORDER
        );
        if (count($matches) === 0) {
            return [];
        }

        foreach ($matches as $match) {
            if (isset($match[1])) {
                $nsParts = explode('\\', $match[1]);
                $className = ltrim(end($nsParts), '\\');
                if (in_array($className, $exceptionClassNames, true)
                    || in_array(strtolower($className), $endOfUseStatements, true)
                ) {
                    continue;
                }
                $tokens[$key]->setContent(strtr($tokens[$key]->getContent(), [$match[1] => $className]));
                $namespaces[] = ltrim($match[1], '\\');
                $endOfUseStatements[] = strtolower(ltrim($className, '\\'));
            }
        }

        if (isset($namespaces)) {
            foreach ($namespaces as $namespace) {
                $this->insertUseStatement($tokens, $namespaceIndex, $namespace);
            }
        }

        return $endOfUseStatements;
    }

    /**
     * @param Tokens $tokens
     * @param int $namespaceIndex
     * @param string $useStatementContent
     * @return bool
     */
    private function insertUseStatement(Tokens $tokens, $namespaceIndex, $useStatementContent)
    {
        $importedClasses = $this->getImportedClasses($tokens);
        if (in_array(ltrim($useStatementContent, '\\'), $importedClasses, true)) {
            return false;
        }

        $classIndex = $tokens->getNextTokenOfKind(0, [new Token([T_CLASS, 'class'])]);
        $className = $tokens[$tokens->getNextMeaningfulToken($classIndex)]->getContent();

        $insertIndex = $tokens->getNextTokenOfKind($namespaceIndex, [';']) + 1;
        $tokens->insertAt($insertIndex, new Token([T_WHITESPACE, "\n"]));
        $tokens->insertAt(++$insertIndex, new Token([T_USE, 'use']));
        $tokens->insertAt(++$insertIndex, new Token([T_WHITESPACE, ' ']));

        $useStatement = array_values(array_filter(explode('\\', $useStatementContent)));
        foreach ($useStatement as $key => $item) {
            $tokens->insertAt(++$insertIndex, new Token([T_STRING, $item]));
            if ($key !== count($useStatement) - 1) {
                $tokens->insertAt(++$insertIndex, new Token([T_NS_SEPARATOR, '\\']));
            }
        }

        if (end($useStatement) === $className) {
            $tokens->insertAt(++$insertIndex, new Token([T_WHITESPACE, ' ']));
            $tokens->insertAt(++$insertIndex, new Token([T_AS, 'as']));
            $tokens->insertAt(++$insertIndex, new Token([T_WHITESPACE, ' ']));
            $tokens->insertAt(++$insertIndex, new Token([T_STRING, 'Base' . $className]));

            $extendsIndex = $tokens->getNextTokenOfKind(0, [new Token([T_EXTENDS, 'extends'])]);
            $tokens->clearRange($extendsIndex + 1, $extendsIndex + count($useStatement) * 2 + 1);

            $tokens->insertAt(++$extendsIndex, new Token([T_WHITESPACE, ' ']));
            $tokens->insertAt(++$extendsIndex, new Token([T_STRING, 'Base' . $className]));
        }

        $tokens->insertAt(++$insertIndex, new Token(';'));

        return true;
    }

    private function getImportedClasses(Tokens $tokens)
    {
        $copy = clone $tokens;
        $usages = [];
        for ($i = 0; $i < count($copy); $i++) {
            if ($copy[$i]->isGivenKind(T_USE)) {
                $namespace = '';
                while ($copy[$i]->getContent() !== ';') {
                    if (
                        $copy[$i]->isGivenKind(T_NS_SEPARATOR)
                        || $copy[$i]->isGivenKind(T_STRING)
                    ) {
                        $namespace .= $copy[$i]->getContent();
                    }
                    $i++;
                }
                $usages[] = $namespace;
            }
            $i++;
        }

        return array_filter($usages);
    }

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     * @return int|null
     */
    private function findNamespaceEndIndex(Tokens $tokens, $startIndex)
    {
        for ($i = $startIndex; $i < $tokens->count() - 1; ++$i) {
            if ($tokens[$i + 1]->isGivenKind(T_WHITESPACE)
                || $tokens[$i + 1]->equals('(')
                || $tokens[$i + 1]->equals(')')
                || $tokens[$i + 1]->equals(';')
                || $tokens[$i + 1]->isGivenKind(T_DOUBLE_COLON)
            ) {
                return $i;
            }
        }
        return null;
    }
}
