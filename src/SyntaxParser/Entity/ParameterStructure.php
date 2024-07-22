<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser\Entity;

use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;

class ParameterStructure
{
    private ?string $name;

    private ?string $typeHintContent;

    private ?string $typeHintFullClass;

    private ?ItemInterface $typeHintItem;

    private ?string $defaultValue;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): self
    {
        $this->defaultValue = $defaultValue;

        return $this;
    }

    public function getTypeHintContent(): ?string
    {
        return $this->typeHintContent;
    }

    public function setTypeHintContent(?string $typeHintContent): self
    {
        $this->typeHintContent = $typeHintContent;

        return $this;
    }

    public function getTypeHintFullClass(): ?string
    {
        return $this->typeHintFullClass;
    }

    public function setTypeHintFullClass(?string $typeHintFullClass): self
    {
        $this->typeHintFullClass = $typeHintFullClass;

        return $this;
    }

    public function getTypeHintItem(): ?ItemInterface
    {
        return $this->typeHintItem;
    }

    public function setTypeHintItem(?ItemInterface $typeHintItem): self
    {
        $this->typeHintItem = $typeHintItem;

        return $this;
    }
}
