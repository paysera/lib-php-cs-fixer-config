<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser\Entity;

class ImportedClasses
{
    /**
     * @var string|null
     */
    private $currentNamespace;

    private $classMap;
    private $lowercasedUsage;

    public function __construct()
    {
        $this->classMap = [];
        $this->lowercasedUsage = [];
    }

    public function registerImport(string $importedAs, string $className)
    {
        $this->classMap[$importedAs] = ltrim($className, '\\');
        $this->lowercasedUsage[mb_strtolower($importedAs)] = $importedAs;
    }

    /**
     * @return string|null
     */
    public function getCurrentNamespace()
    {
        return $this->currentNamespace;
    }

    /**
     * @param string|null $currentNamespace
     * @return $this
     */
    public function setCurrentNamespace($currentNamespace): self
    {
        $this->currentNamespace = $currentNamespace;
        return $this;
    }

    public function isImported(string $importAs): bool
    {
        return isset($this->lowercasedUsage[mb_strtolower($importAs)]);
    }

    /**
     * @param string $importedAs
     * @return string|null
     */
    public function getFullClassName(string $importedAs)
    {
        $normalizedImportedAs = $this->lowercasedUsage[mb_strtolower($importedAs)] ?? null;
        if ($normalizedImportedAs === null) {
            return null;
        }

        return $this->classMap[$normalizedImportedAs];
    }

    /**
     * @param string $fullClassName
     * @return string|null
     */
    public function getImportedAs(string $fullClassName)
    {
        $key = array_search(ltrim($fullClassName, '\\'), $this->classMap, true);
        return $key === false ? null : $key;
    }
}
