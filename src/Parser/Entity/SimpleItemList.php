<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

use RuntimeException;

class SimpleItemList implements ItemInterface
{
    private $itemList;

    /**
     * @var callable
     */
    private $replaceCallback;

    /**
     * @param array|ItemInterface[] $itemList
     */
    public function __construct(array $itemList)
    {
        $this->itemList = $itemList;
    }

    public function lastToken(): ContextualToken
    {
        return end($this->itemList)->lastToken();
    }

    public function firstToken(): ContextualToken
    {
        return reset($this->itemList)->firstToken();
    }

    /**
     * @return array|ItemInterface[]
     */
    public function getItemList()
    {
        return $this->itemList;
    }

    /**
     * @param callable $replaceCallback
     */
    public function setReplaceCallback(callable $replaceCallback)
    {
        $this->replaceCallback = $replaceCallback;
    }

    public function replaceWith(ItemInterface $item)
    {
        if ($item instanceof SimpleItemList && $item->itemList === $this->itemList) {
            return;
        }

        if ($this->replaceCallback === null) {
            throw new RuntimeException('Cannot replace as this list was not set up for this');
        }

        call_user_func($this->replaceCallback, $item);
    }

    public function getComplexItemLists(): array
    {
        $resultGroups = [];
        foreach ($this->itemList as $item) {
            $resultGroups[] = $item->getComplexItemLists();
        }

        return count($resultGroups) > 0 ? array_merge(...$resultGroups) : [];
    }

    public function isSplitIntoSeveralLines(): bool
    {
        return (
            $this->firstToken()->isSplitIntoSeveralLines()
            || $this->lastToken()->isSplitIntoSeveralLines()
        );
    }

    public function getContent()
    {
        return array_reduce($this->itemList, function ($result, ItemInterface $item) {
            return $result . $item->getContent();
        }, '');
    }

    public function equalsToItem(ItemInterface $item): bool
    {
        if (!(
            $item instanceof $this
            && $this instanceof $item
            && count($item->itemList) === count($this->itemList)
        )) {
            return false;
        }

        foreach ($this->itemList as $key => $internalItem) {
            if (!$internalItem->equalsToItem($item->itemList[$key])) {
                return false;
            }
        }

        return true;
    }
}
