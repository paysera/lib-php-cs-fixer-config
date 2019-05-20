<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\ImportedClasses;
use PhpCsFixer\Tokenizer\Tokens;

class ImportedClassesParser
{
    private $builder;
    
    public function __construct(ContextualTokenBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function parseImportedClasses(Tokens $tokens)
    {
        $token = $this->builder->buildFromTokens($tokens);

        $importedClasses = new ImportedClasses();
        while ($token !== null) {
            if ($token->isGivenKind(T_USE)) {
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
}
