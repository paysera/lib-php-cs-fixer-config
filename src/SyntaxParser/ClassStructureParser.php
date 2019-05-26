<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\SimpleItemList;
use Paysera\PhpCsFixerConfig\Parser\Parser;
use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\ClassStructure;
use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\FunctionStructure;
use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\ParameterStructure;
use PhpCsFixer\DocBlock\DocBlock;
use RuntimeException;

class ClassStructureParser
{
    private $parser;
    private $importedClassesParser;

    public function __construct(Parser $parser, ImportedClassesParser $importedClassesParser)
    {
        $this->parser = $parser;
        $this->importedClassesParser = $importedClassesParser;
    }

    /**
     * @param ContextualToken $firstToken
     * @return ClassStructure|null
     */
    public function parseClassStructure(ContextualToken $firstToken)
    {
        $token = $firstToken;
        while ($token !== null) {
            if (!$token->isGivenKind(T_CLASS)) {
                $token = $token->getNextToken();
                continue;
            }

            $classStructure = new ClassStructure();
            $classStructure->setName($token->nextNonWhitespaceToken()->getContent());
            $classStructure->setFirstToken($token);
            $methods = $this->parseMethods($token->nextTokenWithContent('{')->nextToken());
            $classStructure->setMethods($methods);

            $this->appendImportedClasses($classStructure, $firstToken);

            return $classStructure;
        }

        return null;
    }

    /**
     * @param ContextualToken $token
     * @return array|FunctionStructure[]
     */
    public function parseFunctionStructures(ContextualToken $token): array
    {
        $functions = [];
        while ($token !== null) {
            if (!$token->isGivenKind(T_FUNCTION)) {
                $token = $token->getNextToken();
                continue;
            }

            $function = $this->parseFunction($token);
            $functions[] = $function;

            $token = $function->getContentsItem()->lastToken()->getNextToken();
        }

        return $functions;
    }

    private function parseMethods(ContextualToken $token): array
    {
        $methods = [];

        // functions "eat" their own internal `}`, so we're searching for end of class body here
        while ($token->getContent() !== '}') {
            if (!$token->isGivenKind(T_FUNCTION)) {
                $token = $token->getNextToken();
                continue;
            }

            $method = $this->parseFunction($token);
            $methods[] = $method;

            $token = $method->getContentsItem()->lastToken()->nextToken();
        }

        return $methods;
    }

    private function parseFunction(ContextualToken $functionToken): FunctionStructure
    {
        $method = new FunctionStructure();
        $method->setName($functionToken->nextNonWhitespaceToken()->getContent());

        $keywords = [];
        $token = $functionToken->previousNonWhitespaceToken();
        while ($token->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL, T_ABSTRACT, T_STATIC])) {
            $keywords[] = $token->getContent();
            $token = $token->previousNonWhitespaceToken();
        }
        $method->setKeywords(array_reverse($keywords));
        if ($token->isGivenKind(T_DOC_COMMENT)) {
            $method->setPhpDoc(new DocBlock($token->getContent()));
        } else {
            $token = $token->nextNonWhitespaceToken();
        }

        $method->setFirstToken($token);

        $method->setParameters($this->parseParameters($functionToken));

        $token = $functionToken;
        while ($token->getContent() !== ';' && $token->getContent() !== '{') {
            $token = $token->nextToken();
        }

        if ($token->getContent() === '{') {
            $groupedItem = $this->parser->parseUntil($token, '}');
            $method->setContentsItem($groupedItem);
        } else {
            $method->setContentsItem($token);
        }

        return $method;
    }

    private function parseParameters(ContextualToken $functionToken): array
    {
        $token = $functionToken->nextTokenWithContent('(');

        $unparsedParameters = [];
        $currentGroup = [];
        $currentWordTokens = [];
        do {
            $token = $token->nextToken();

            $separator = in_array($token->getContent(), [',', ')'], true);

            if (
                ($token->isWhitespace() || $separator)
                && count($currentWordTokens) > 0
            ) {
                $currentGroup[] = new SimpleItemList($currentWordTokens);
                $currentWordTokens = [];
            }

            if ($separator && count($currentGroup) > 0) {
                $unparsedParameters[] = $currentGroup;
                $currentGroup = [];
            }

            if (!$token->isWhitespace() && !$separator) {
                $currentWordTokens[] = $token;
            }

        } while ($token->getContent() !== ')');

        $parameters = [];
        foreach ($unparsedParameters as $group) {
            $parameters[] = $this->parseParameter($group);
        }

        return $parameters;
    }

    /**
     * @param array|SimpleItemList[] $itemGroup
     * @return ParameterStructure
     */
    private function parseParameter(array $itemGroup): ParameterStructure
    {
        $typeHintContent = null;
        $typeHintItem = null;
        $defaultValue = null;
        if (count($itemGroup) === 1) {
            $name = $itemGroup[0]->getContent();
        } elseif (count($itemGroup) === 2) {
            $typeHintItem = $itemGroup[0];
            $typeHintContent = $itemGroup[0]->getContent();
            $name = $itemGroup[1]->getContent();
        } elseif (count($itemGroup) === 3 && $itemGroup[1]->getContent() === '=') {
            $name = $itemGroup[0]->getContent();
            $defaultValue = $itemGroup[2]->getContent();
        } elseif (count($itemGroup) === 4 && $itemGroup[2]->getContent() === '=') {
            $typeHintItem = $itemGroup[0];
            $typeHintContent = $itemGroup[0]->getContent();
            $name = $itemGroup[1]->getContent();
            $defaultValue = $itemGroup[3]->getContent();
        } else {
            throw new RuntimeException('Cannot parse parameter - unexpected tokens');
        }

        return (new ParameterStructure())
            ->setName($name)
            ->setTypeHintContent($typeHintContent)
            ->setTypeHintItem($typeHintItem)
            ->setDefaultValue($defaultValue)
        ;
    }

    private function appendImportedClasses(ClassStructure $classStructure, ContextualToken $token)
    {
        $importedClasses = $this->importedClassesParser->parseImportedClasses($token);
        $classStructure->setImportedClasses($importedClasses);

        foreach ($classStructure->getMethods() as $method) {
            foreach ($method->getParameters() as $parameter) {
                $fullClassName = $this->resolveFullClassName($parameter, $classStructure);
                if ($fullClassName !== null) {
                    $parameter->setTypeHintFullClass($fullClassName);
                }
            }
        }
    }

    private function resolveFullClassName(ParameterStructure $parameter, ClassStructure $classStructure)
    {
        if ($parameter->getTypeHintContent() === null) {
            return null;
        }

        $importedClasses = $classStructure->getImportedClasses();
        $typeHint = ltrim($parameter->getTypeHintContent(), '?');
        $namespacePrefix = $importedClasses->getCurrentNamespace() !== null
            ? $importedClasses->getCurrentNamespace() . '\\'
            : '';

        if ($typeHint === 'self') {
            return $namespacePrefix . $classStructure->getName();
        }

        if (in_array($typeHint, [
            'array',
            'callable',
            'bool',
            'float',
            'int',
            'string',
            'iterable',
            'object',
        ], true)) {
            return null;
        }

        if ($typeHint[0] === '\\') {
            return ltrim($typeHint, '\\');
        } elseif ($importedClasses->isImported($typeHint)) {
            return $importedClasses->getFullClassName($typeHint);
        }

        return $namespacePrefix . $typeHint;
    }
}
