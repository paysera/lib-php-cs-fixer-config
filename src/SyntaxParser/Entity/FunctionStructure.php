<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser\Entity;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use PhpCsFixer\DocBlock\DocBlock;

class FunctionStructure
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var ItemInterface|null
     */
    private $contentsItem;

    /**
     * @var ContextualToken|null
     */
    private $firstToken;

    /**
     * @var array
     */
    private $keywords;

    /**
     * @var ParameterStructure[]
     */
    private $parameters;

    /**
     * @var DocBlock|null
     */
    private $phpDoc;

    public function __construct()
    {
        $this->keywords = [];
        $this->parameters = [];
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
     * @return ItemInterface|null
     */
    public function getContentsItem()
    {
        return $this->contentsItem;
    }

    /**
     * @param ItemInterface|null $contentsItem
     * @return $this
     */
    public function setContentsItem(ItemInterface $contentsItem): self
    {
        $this->contentsItem = $contentsItem;
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
     * @return array
     */
    public function getKeywords(): array
    {
        return $this->keywords;
    }

    /**
     * @param array $keywords
     * @return $this
     */
    public function setKeywords(array $keywords): self
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @return ParameterStructure[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param ParameterStructure[] $parameters
     * @return $this
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * @return DocBlock|null
     */
    public function getPhpDoc()
    {
        return $this->phpDoc;
    }

    /**
     * @param DocBlock|null $phpDoc
     * @return $this
     */
    public function setPhpDoc(DocBlock $phpDoc): self
    {
        $this->phpDoc = $phpDoc;
        return $this;
    }
}
