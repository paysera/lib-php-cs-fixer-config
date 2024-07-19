<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\SyntaxParser\Entity;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use PhpCsFixer\DocBlock\DocBlock;

class FunctionStructure
{
    private ?string $name;

    private ?ItemInterface $contentsItem;

    private ?ContextualToken $firstToken;

    private array $keywords;

    /**
     * @var ParameterStructure[]
     */
    private array $parameters;

    private ?DocBlock $phpDoc;

    public function __construct()
    {
        $this->keywords = [];
        $this->parameters = [];
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

    public function getContentsItem(): ?ItemInterface
    {
        return $this->contentsItem;
    }

    public function setContentsItem(ItemInterface $contentsItem): self
    {
        $this->contentsItem = $contentsItem;
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

    public function getKeywords(): array
    {
        return $this->keywords;
    }

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
     */
    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getPhpDoc(): ?DocBlock
    {
        return $this->phpDoc;
    }

    public function setPhpDoc(DocBlock $phpDoc): self
    {
        $this->phpDoc = $phpDoc;
        return $this;
    }
}
