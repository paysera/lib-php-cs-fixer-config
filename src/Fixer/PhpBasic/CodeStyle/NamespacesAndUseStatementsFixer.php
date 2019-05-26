<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Parser\ContextualTokenBuilder;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\EmptyToken;
use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\ImportedClasses;
use Paysera\PhpCsFixerConfig\Parser\Exception\NoMoreTokensException;
use Paysera\PhpCsFixerConfig\SyntaxParser\ImportedClassesParser;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class NamespacesAndUseStatementsFixer extends AbstractFixer
{
    private $docBlockAnnotations;
    private $importedClassesParser;
    private $contextualTokenBuilder;

    public function __construct()
    {
        parent::__construct();
        $this->contextualTokenBuilder = new ContextualTokenBuilder();
        $this->importedClassesParser = new ImportedClassesParser();
        $this->docBlockAnnotations = [
            '@throws',
            '@return',
            '@returns',
            '@param',
            '@var',
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            'If class has a namespace, we use "use" statements instead of providing full namespace.
            This applies to php-doc comments, too.
            
            Does not process namespace without root. Example: Some\Entity\Operation.
            Risky as class can be imported or aliased with same name as another class inside that namespace.
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

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $token = $this->contextualTokenBuilder->buildFromTokens($tokens);

        $importedClasses = $this->importedClassesParser->parseImportedClasses($token);

        $firstToken = (new EmptyToken())->setNextContextualToken($token);
        $lastNamespaceToken = null;

        while ($token !== null) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $lastNamespaceToken = $token->nextTokenWithContent(';');
            }

            $this->processToken($token, $importedClasses, $lastNamespaceToken);

            $token = $token->getNextToken();
        }

        $this->contextualTokenBuilder->overrideTokens($tokens, $firstToken);
    }

    private function processToken(ContextualToken $token, ImportedClasses $importedClasses, ContextualToken $lastNamespaceToken = null)
    {
        if ($token->isGivenKind(T_DOC_COMMENT) && $lastNamespaceToken !== null) {
            $this->fixDocBlockContent(
                $token,
                $lastNamespaceToken,
                $importedClasses
            );
        }

        if (!$token->isGivenKind(T_NS_SEPARATOR)) {
            return;
        }

        if (
            $lastNamespaceToken === null
            || $token->previousToken()->isGivenKind(T_STRING)
            || $token->previousToken()->previousToken()->isGivenKind(T_USE)
        ) {
            return;
        }

        $fullyQualifiedClassName = $this->gatherFullClassName($token);

        $importAs = $this->importClass($lastNamespaceToken, $importedClasses, $fullyQualifiedClassName);
        if ($importAs !== null) {
            $this->replaceFullClassName($token, $importAs);
        }
    }

    private function fixDocBlockContent(
        ContextualToken $phpDocToken,
        ContextualToken $lastNamespaceToken,
        ImportedClasses $importedClasses
    ) {
        $content = $phpDocToken->getContent();
        preg_match_all(
            '#(?<=' . implode('|', $this->docBlockAnnotations) . ')\s(\\\[\w\\\]*\\\?(\w*))\s#',
            $content,
            $matches,
            PREG_SET_ORDER
        );
        if (count($matches) === 0) {
            return;
        }

        foreach ($matches as $match) {
            if (isset($match[1])) {
                $fullClassName = $match[1];

                $importedAs = $this->importClass($lastNamespaceToken, $importedClasses, $fullClassName);
                if ($importedAs === null) {
                    continue;
                }

                $phpDocToken->setContent(preg_replace(
                    '/(\s)' . preg_quote($fullClassName, '/') . '(\s)/',
                    '$1' . $importedAs . '$2',
                    $phpDocToken->getContent()
                ));
            }
        }
    }

    private function importClass(
        ContextualToken $lastNamespaceToken,
        ImportedClasses $importedClasses,
        string $fullClassName
    ) {
        $nsParts = explode('\\', $fullClassName);
        $className = end($nsParts);

        $importedAs = $importedClasses->getImportedAs($fullClassName);
        if ($importedAs !== null) {
            return $importedAs;
        }

        $importAs = null;
        try {
            $classToken = $lastNamespaceToken->firstToken()->nextTokenWithContent('class');
            $currentClassName = $classToken->nextNonWhitespaceToken()->getContent();
        } catch (NoMoreTokensException $exception) {
            $currentClassName = null;
        }

        if ($currentClassName !== null && $currentClassName === $className) {
            $className = 'Base' . $className;
            $importAs = $className;
        }

        if ($importedClasses->isImported($className)) {
            return null;
        }
        $this->insertUseStatement($lastNamespaceToken, $fullClassName, $importAs);
        $importedClasses->registerImport($className, $fullClassName);
        return $className;
    }

    private function insertUseStatement(
        ContextualToken $lastNamespaceToken,
        string $useStatementContent,
        string $importAs = null
    ) {
        $possibleUseToken = $lastNamespaceToken->nextNonWhitespaceToken();
        if ($possibleUseToken->isGivenKind(T_USE)) {
            $insertAtToken = $this->findWhitespaceTokenAfterLastUseStatement($possibleUseToken);
            $insertAtToken->insertBefore(new ContextualToken("\n"));
        } else {
            $insertAtToken = $lastNamespaceToken->nextToken();
            $insertAtToken->insertBefore(new ContextualToken("\n\n"));
        }

        $this->insertImport($insertAtToken, $useStatementContent, $importAs);
    }

    private function insertImport(ContextualToken $insertAtToken, string $import, string $importAs = null)
    {
        $tokens = [];
        $tokens[] = new ContextualToken([T_USE, 'use']);
        $tokens[] = new ContextualToken(' ');

        $useStatement = array_values(array_filter(explode('\\', $import)));
        foreach ($useStatement as $key => $item) {
            $tokens[] = new ContextualToken([T_STRING, $item]);
            if ($key !== count($useStatement) - 1) {
                $tokens[] = new ContextualToken([T_NS_SEPARATOR, '\\']);
            }
        }

        if ($importAs !== null) {
            $tokens[] = new ContextualToken(' ');
            $tokens[] = new ContextualToken([T_AS, 'as']);
            $tokens[] = new ContextualToken(' ');
            $tokens[] = new ContextualToken([T_STRING, $importAs]);
        }
        $tokens[] = new ContextualToken(';');

        $insertAtToken->insertSequenceBefore($tokens);
    }

    private function gatherFullClassName(ContextualToken $startToken): string
    {
        $token = $startToken;
        $className = '';
        while ($token->isGivenKind(T_STRING) || $token->isGivenKind(T_NS_SEPARATOR)) {
            $className .= $token->getContent();
            $token = $token->nextToken();
        }

        return $className;
    }

    private function replaceFullClassName(ContextualToken $startToken, string $className)
    {
        $startToken->replaceWithTokens([new ContextualToken([T_STRING, $className])]);
        $token = $startToken->nextToken();
        while ($token->isGivenKind(T_STRING) || $token->isGivenKind(T_NS_SEPARATOR)) {
            $token->replaceWithTokens([]);
            $token = $token->nextToken();
        }
    }

    private function findWhitespaceTokenAfterLastUseStatement(ContextualToken $possibleUseToken)
    {
        $token = $possibleUseToken->nextToken();
        while (
            $token->isGivenKind(T_USE)
            || $token->isGivenKind(T_STRING)
            || $token->getContent() === '\\'
            || $token->isWhitespace()
            || $token->getContent() === ';'
        ) {
            $token = $token->nextToken();
        }
        return $token->previousToken();
    }
}
