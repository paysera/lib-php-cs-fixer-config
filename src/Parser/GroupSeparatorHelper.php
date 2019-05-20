<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use Paysera\PhpCsFixerConfig\Parser\Entity\SeparatedItemList;
use Paysera\PhpCsFixerConfig\Parser\Entity\SimpleItemList;
use Paysera\PhpCsFixerConfig\Parser\Entity\TypedItem;

class GroupSeparatorHelper
{
    /**
     * Return SeparatedItemList built by splitting items with separator and excluding whitespace from the items.
     *
     * This:
     *      [space]$a[space]&&[space]$b[space]||[space]$c[space]
     * When split by "||", would be split into this:
     *      prefix  | item                 | separator        | item | postfix
     *      [space] | $a[space]&&[space]$b | [space]||[space] | $c   | [space]
     *
     * @param ItemInterface[] $itemList
     * @param string $separator
     * @return SeparatedItemList|null
     */
    public function regroupListBySeparator(array $itemList, string $separator)
    {
        $list = new SeparatedItemList($separator);

        $beginning = true;
        $lastWhiteSpace = [];   // might also include comments
        $separatorGroup = [];
        $currentGroup = [];
        foreach ($itemList as $item) {
            if ($item instanceof ContextualToken && ($item->isWhitespace() || $item->isComment())) {
                $lastWhiteSpace[] = $item;
                continue;
            }

            if ($item instanceof ContextualToken && $item->getContent() === $separator) {
                if (count($lastWhiteSpace) > 0) {
                    $separatorGroup = array_merge($separatorGroup, $lastWhiteSpace);
                    $lastWhiteSpace = [];
                }

                $separatorGroup[] = $item;

                if (count($currentGroup) > 0) {
                    $list->addContentItemGroup($currentGroup);
                    $currentGroup = [];
                }
                continue;
            }

            if ($beginning && count($lastWhiteSpace) > 0) {
                $list->addPrefixWhitespaceItem(
                    count($lastWhiteSpace) > 1 ? new SimpleItemList($lastWhiteSpace) : $lastWhiteSpace[0]
                );
                $lastWhiteSpace = [];
            }
            $beginning = false;

            if (count($separatorGroup) > 0) {
                if (count($lastWhiteSpace) > 0) {
                    $separatorGroup = array_merge($separatorGroup, $lastWhiteSpace);
                    $lastWhiteSpace = [];
                }
                $list->addSeparatorGroup($separatorGroup);
                $separatorGroup = [];
            }

            if (count($lastWhiteSpace) > 0) {
                $currentGroup = array_merge($currentGroup, $lastWhiteSpace);
                $lastWhiteSpace = [];
            }

            $currentGroup[] = $item;
        }

        if (count($currentGroup) > 0) {
            $list->addContentItemGroup($currentGroup);
        }

        if (count($separatorGroup) > 0) {
            $list->addSeparatorGroup($separatorGroup, TypedItem::TYPE_SEPARATOR_AFTER_CONTENTS);
        }

        if (count($lastWhiteSpace) > 0) {
            $list->addPostfixWhitespaceItem(
                count($lastWhiteSpace) > 1 ? new SimpleItemList($lastWhiteSpace) : $lastWhiteSpace[0]
            );
        }

        if ($list->countContentItems() <= 1) {
            return null;
        }

        return $list;
    }
}
