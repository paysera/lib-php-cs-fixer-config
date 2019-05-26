<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser\Entity;

use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;

class ParameterStructure
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $typeHintContent;

    /**
     * @var string|null
     */
    private $typeHintFullClass;

    /**
     * @var ItemInterface|null
     */
    private $typeHintItem;

    /**
     * @var string|null
     */
    private $defaultValue;

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param string|null $defaultValue
     * @return $this
     */
    public function setDefaultValue($defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTypeHintContent()
    {
        return $this->typeHintContent;
    }

    /**
     * @param string|null $typeHintContent
     * @return $this
     */
    public function setTypeHintContent($typeHintContent): self
    {
        $this->typeHintContent = $typeHintContent;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTypeHintFullClass()
    {
        return $this->typeHintFullClass;
    }

    /**
     * @param string|null $typeHintFullClass
     * @return $this
     */
    public function setTypeHintFullClass($typeHintFullClass): self
    {
        $this->typeHintFullClass = $typeHintFullClass;
        return $this;
    }

    /**
     * @return ItemInterface|null
     */
    public function getTypeHintItem()
    {
        return $this->typeHintItem;
    }

    /**
     * @param ItemInterface|null $typeHintItem
     * @return $this
     */
    public function setTypeHintItem($typeHintItem): self
    {
        $this->typeHintItem = $typeHintItem;
        return $this;
    }
}
