<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser\Entity;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;

class ClassStructure
{
    private ?string $name;

    private ?ContextualToken $firstToken;

    private array $methods;

    private ImportedClasses $importedClasses;

    public function __construct()
    {
        $this->methods = [];
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getFirstToken(): ?ContextualToken
    {
        return $this->firstToken;
    }

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

    public function getConstructorMethod(): ?FunctionStructure
    {
        foreach ($this->methods as $method) {
            if ($method->getName() === '__construct') {
                return $method;
            }
        }

        return null;
    }

    public function getImportedClasses(): ImportedClasses
    {
        return $this->importedClasses;
    }

    public function setImportedClasses(ImportedClasses $importedClasses): self
    {
        $this->importedClasses = $importedClasses;

        return $this;
    }
}
