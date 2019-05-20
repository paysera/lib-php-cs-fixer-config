<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

class ConstructItem implements ItemInterface
{
    private $prefixItem;
    private $postfixItem;

    /**
     * @var ItemInterface
     */
    private $contentItem;

    public function __construct(ItemInterface $prefixItem, ItemInterface $contentItem, ItemInterface $postfixItem)
    {
        $this->prefixItem = $prefixItem;
        $this->setContentItem($contentItem);
        $this->postfixItem = $postfixItem;
    }

    public function lastToken(): ContextualToken
    {
        return $this->postfixItem->lastToken();
    }

    public function firstToken(): ContextualToken
    {
        return $this->prefixItem->firstToken();
    }

    /**
     * @return ItemInterface
     */
    public function getPrefixItem(): ItemInterface
    {
        return $this->prefixItem;
    }

    /**
     * @return ItemInterface
     */
    public function getPostfixItem(): ItemInterface
    {
        return $this->postfixItem;
    }

    /**
     * @return ItemInterface
     */
    public function getContentItem(): ItemInterface
    {
        return $this->contentItem;
    }

    private function setContentItem(ItemInterface $contentItem)
    {
        $this->contentItem = $contentItem;

        if ($contentItem instanceof SimpleItemList) {
            $contentItem->setReplaceCallback(function (ItemInterface $item) {
                $this->setContentItem($item);
            });
        }
    }

    public function getComplexItemLists(): array
    {
        return $this->contentItem->getComplexItemLists();
    }

    public function isSplitIntoSeveralLines(): bool
    {
        return $this->contentItem->isSplitIntoSeveralLines();
    }

    public function getContent()
    {
        return $this->prefixItem->getContent() . $this->contentItem->getContent() . $this->postfixItem->getContent();
    }

    public function equalsToItem(ItemInterface $item): bool
    {
        return (
            $item instanceof $this
            && $this instanceof $item
            && $item->prefixItem === $this->prefixItem
            && $item->postfixItem === $this->postfixItem
            && $item->contentItem->equalsToItem($this->contentItem)
        );
    }
}
