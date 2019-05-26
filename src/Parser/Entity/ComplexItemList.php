<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

use Generator;

/**
 * @php-cs-fixer-ignore Paysera/php_basic_feature_visibility_properties
 */
class ComplexItemList implements ItemInterface
{
    /**
     * @var TypedItem[]
     */
    protected $typedItemList;

    public function __construct()
    {
        $this->typedItemList = [];
    }

    public function lastToken(): ContextualToken
    {
        return end($this->typedItemList)->getItem()->lastToken();
    }

    public function firstToken(): ContextualToken
    {
        return reset($this->typedItemList)->getItem()->firstToken();
    }

    public function getContent()
    {
        return array_reduce($this->typedItemList, function (string $result, TypedItem $item) {
            return $result . $item->getItem()->getContent();
        }, '');
    }

    public function addPrefixItem(ItemInterface $prefixItem)
    {
        $this->addItemAtEnd($prefixItem, TypedItem::TYPE_PREFIX);

        return $this;
    }

    public function addPrefixWhitespaceItem(ItemInterface $prefixItem)
    {
        $this->addItemAtEnd($prefixItem, TypedItem::TYPE_PREFIX_WHITESPACE);

        return $this;
    }

    public function addContentItemGroup(array $items)
    {
        $groupedItem = count($items) === 1 ? $items[0] : new SimpleItemList($items);
        $this->addContentItem($groupedItem);

        return $this;
    }

    public function addContentItem(ItemInterface $item)
    {
        $this->addItemAtEnd($item, TypedItem::TYPE_CONTENT);

        return $this;
    }

    public function addPostfixWhitespaceItem(ItemInterface $postfixItem)
    {
        $this->addItemAtEnd($postfixItem, TypedItem::TYPE_POSTFIX_WHITESPACE);

        return $this;
    }

    public function addPostfixItem(ItemInterface $postfixItem)
    {
        $this->addItemAtEnd($postfixItem, TypedItem::TYPE_POSTFIX);

        return $this;
    }

    protected function addItemAtEnd(ItemInterface $item, string $type)
    {
        $this->typedItemList[] = new TypedItem($item, $type);
        $this->setItemAtKey(count($this->typedItemList) - 1, $item);
    }

    protected function setItemAtKey(int $key, ItemInterface $item)
    {
        $this->typedItemList[$key] = new TypedItem($item, $this->typedItemList[$key]->getType());

        if ($item instanceof SimpleItemList) {
            $item->setReplaceCallback(function (ItemInterface $item) use ($key) {
                $this->setItemAtKey($key, $item);
            });
        }
    }

    public function countContentItems(): int
    {
        $count = 0;
        foreach ($this->typedItemList as $typedItem) {
            if ($typedItem->getType() === TypedItem::TYPE_CONTENT) {
                $count++;
            }
        }

        return $count;
    }

    public function getComplexItemLists(): array
    {
        $resultGroups = [];
        $resultGroups[] = [$this];
        foreach ($this->typedItemList as $typedItem) {
            $resultGroups[] = $typedItem->getItem()->getComplexItemLists();
        }

        return array_merge(...$resultGroups);
    }

    /**
     * This method is used for detecting new lines inside other elements
     * Separated elements, if they have new lines, are indented and each of them is in it's own line
     */
    public function isSplitIntoSeveralLines(): bool
    {
        foreach ($this->typedItemList as $typedItem) {
            if (
                $typedItem->getType() !== TypedItem::TYPE_CONTENT
                && $typedItem->getItem()->isSplitIntoSeveralLines()
            ) {
                return true;
            }
        }

        return false;
    }

    public function getFirstPrefixWhitespaceItem()
    {
        return $this->getFirstItemOfType(TypedItem::TYPE_PREFIX_WHITESPACE);
    }

    public function getFirstPrefixItem()
    {
        return $this->getFirstItemOfType(TypedItem::TYPE_PREFIX);
    }

    public function getFirstPostfixWhitespaceItem()
    {
        return $this->getFirstItemOfType(TypedItem::TYPE_POSTFIX_WHITESPACE);
    }

    public function getFirstPostfixItem()
    {
        return $this->getFirstItemOfType(TypedItem::TYPE_POSTFIX);
    }

    /**
     * @return ItemInterface[]|Generator
     */
    public function getContentItems()
    {
        return $this->getItemsOfType(TypedItem::TYPE_CONTENT);
    }

    /**
     * @param string $type
     * @return ItemInterface[]|Generator
     */
    protected function getItemsOfType(string $type): Generator
    {
        foreach ($this->typedItemList as $typedItem) {
            if ($typedItem->getType() === $type) {
                yield $typedItem->getItem();
            }
        }
    }

    /**
     * @param string $type
     * @return ItemInterface
     */
    protected function getFirstItemOfType(string $type)
    {
        foreach ($this->getItemsOfType($type) as $item) {
            return $item;
        }

        return null;
    }

    public function equalsToItem(ItemInterface $item): bool
    {
        if (!(
            $item instanceof $this
            && $this instanceof $item
            && count($item->typedItemList) === count($this->typedItemList)
        )) {
            return false;
        }

        foreach ($this->typedItemList as $key => $typedItem) {
            $typedItemToCompare = $item->typedItemList[$key];
            if (
                $typedItem->getType() !== $typedItemToCompare->getType()
                || !$typedItem->getItem()->equalsToItem($typedItemToCompare->getItem())
            ) {
                return false;
            }
        }

        return true;
    }
}
