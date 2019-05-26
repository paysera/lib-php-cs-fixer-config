<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser\Entity;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;

class ClassStructure
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var ContextualToken|null
     */
    private $firstToken;

    /**
     * @var FunctionStructure[]
     */
    private $methods;

    /**
     * @var ImportedClasses
     */
    private $importedClasses;

    public function __construct()
    {
        $this->methods = [];
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return ContextualToken|null
     */
    public function getFirstToken()
    {
        return $this->firstToken;
    }

    /**
     * @param ContextualToken|null $firstToken
     * @return $this
     */
    public function setFirstToken(ContextualToken $firstToken): self
    {
        $this->firstToken = $firstToken;
        return $this;
    }

    /**
     * @return FunctionStructure[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param FunctionStructure[] $methods
     * @return $this
     */
    public function setMethods(array $methods): self
    {
        $this->methods = $methods;
        return $this;
    }

    /**
     * @return FunctionStructure|null
     */
    public function getConstructorMethod()
    {
        foreach ($this->methods as $method) {
            if ($method->getName() === '__construct') {
                return $method;
            }
        }

        return null;
    }

    /**
     * @return ImportedClasses
     */
    public function getImportedClasses(): ImportedClasses
    {
        return $this->importedClasses;
    }

    /**
     * @param ImportedClasses $importedClasses
     * @return $this
     */
    public function setImportedClasses(ImportedClasses $importedClasses): self
    {
        $this->importedClasses = $importedClasses;
        return $this;
    }
}
