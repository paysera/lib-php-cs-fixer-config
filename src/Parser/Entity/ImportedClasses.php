<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

class ImportedClasses
{
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
        $this->lowercasedUsage[mb_strtolower($importedAs)] = true;
    }

    public function isNameTaken(string $importAs): bool
    {
        return isset($this->lowercasedUsage[mb_strtolower($importAs)]);
    }

    /**
     * @param string $className
     * @return string|null
     */
    public function getImportedAs(string $className)
    {
        $key = array_search(ltrim($className, '\\'), $this->classMap, true);
        return $key === false ? null : $key;
    }
}
