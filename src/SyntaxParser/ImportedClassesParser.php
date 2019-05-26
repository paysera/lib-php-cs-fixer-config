<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\ImportedClasses;

class ImportedClassesParser
{
    public function parseImportedClasses(ContextualToken $firstToken)
    {
        $importedClasses = new ImportedClasses();
        $token = $firstToken;
        while ($token !== null) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $token = $this->parseNamespaceStatement($token, $importedClasses);
            } elseif ($token->isGivenKind(T_USE)) {
                $token = $this->parseUseStatement($token, $importedClasses);
            }

            $token = $token->getNextToken();
        }

        return $importedClasses;
    }

    private function parseUseStatement(ContextualToken $useToken, ImportedClasses $importedClasses): ContextualToken
    {
        $token = $useToken->nextToken();

        $className = null;
        $importName = null;
        $currentContent = '';
        while ($token->getContent() !== ';') {
            if ($token->isGivenKind(T_AS)) {
                $className = $currentContent;
                $currentContent = '';
            } elseif (!$token->isWhitespace()) {
                $currentContent .= $token->getContent();
            }
            $token = $token->nextToken();
        }

        if ($className === null) {
            $className = $currentContent;
            preg_match('/[^\\\\]+$/', $className, $matches);
            $importName = $matches[0];
        } else {
            $importName = $currentContent;
        }

        $importedClasses->registerImport($importName, $className);

        return $token;
    }

    private function parseNamespaceStatement(
        ContextualToken $namespaceToken,
        ImportedClasses $importedClasses
    ): ContextualToken {
        $token = $namespaceToken->nextNonWhitespaceToken();
        $namespace = '';
        while ($token->getContent() !== ';' && !$token->isWhitespace()) {
            $namespace .= $token->getContent();
            $token = $token->getNextToken();
        }

        $importedClasses->setCurrentNamespace($namespace);

        return $token;
    }
}
