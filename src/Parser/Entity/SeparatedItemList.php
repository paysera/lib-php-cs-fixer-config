<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

use Generator;

class SeparatedItemList extends ComplexItemList implements ItemInterface
{
    private $separator;

    public function __construct(string $separator)
    {
        parent::__construct();
        $this->separator = $separator;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    /**
     * @param array|ItemInterface[] $items
     * @param string $type
     * @return $this
     */
    public function addSeparatorGroup(array $items, string $type = TypedItem::TYPE_SEPARATOR)
    {
        $groupedItem = count($items) === 1 ? $items[0] : new SimpleItemList($items);
        $this->addItemAtEnd($groupedItem, $type);

        return $this;
    }

    /**
     * @return ItemInterface[]|Generator
     */
    public function getSeparatorItems(): Generator
    {
        return $this->getItemsOfType(TypedItem::TYPE_SEPARATOR);
    }

    /**
     * @return ItemInterface|null
     */
    public function getSeparatorAfterContents()
    {
        return $this->getFirstItemOfType(TypedItem::TYPE_SEPARATOR_AFTER_CONTENTS);
    }

    public function areSeparatorsSplitIntoSeveralLines()
    {
        foreach ($this->typedItemList as $typedItem) {
            if (
                $typedItem->getType() === TypedItem::TYPE_SEPARATOR
                && $typedItem->getItem()->isSplitIntoSeveralLines()
            ) {
                return true;
            }
        }

        return false;
    }
}
