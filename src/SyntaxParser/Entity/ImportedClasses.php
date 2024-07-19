<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser\Entity;

class ImportedClasses
{
    /**
     * @var string|null
     */
    private ?string $currentNamespace;

    private array $classMap;
    private array $lowercasedUsage;

    public function __construct()
    {
        $this->classMap = [];
        $this->lowercasedUsage = [];
    }

    public function registerImport(string $importedAs, string $className): self
    {
        $this->classMap[$importedAs] = ltrim($className, '\\');
        $this->lowercasedUsage[mb_strtolower($importedAs)] = $importedAs;
        return $this;
    }

    public function getCurrentNamespace(): ?string
    {
        return $this->currentNamespace;
    }

    public function setCurrentNamespace(?string $currentNamespace): self
    {
        $this->currentNamespace = $currentNamespace;
        return $this;
    }

    public function isImported(string $importAs): bool
    {
        return isset($this->lowercasedUsage[mb_strtolower($importAs)]);
    }

    public function getFullClassName(string $importedAs): ?string
    {
        $normalizedImportedAs = $this->lowercasedUsage[mb_strtolower($importedAs)] ?? null;
        if ($normalizedImportedAs === null) {
            return null;
        }

        return $this->classMap[$normalizedImportedAs];
    }

    public function getImportedAs(string $fullClassName): ?string
    {
        $key = array_search(ltrim($fullClassName, '\\'), $this->classMap, true);
        return $key === false ? null : $key;
    }
}
