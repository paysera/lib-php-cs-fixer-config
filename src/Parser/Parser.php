<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ComplexItemList;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\EmptyToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use Paysera\PhpCsFixerConfig\Parser\Entity\SimpleItemList;
use RuntimeException;

class Parser
{
    private $groupSeparatorHelper;

    public function __construct(GroupSeparatorHelper $groupSeparatorHelper)
    {
        $this->groupSeparatorHelper = $groupSeparatorHelper;
    }

    public function parseUntil(ItemInterface $prefixItem, string $endTokenValue): ItemInterface
    {
        return $this->groupItems($prefixItem, $endTokenValue);
    }

    /**
     * Groups tokens into ConstructItem.
     * Groups only by different parenthesis, not by internal operators or separators.
     * An example. Each line is separate item, indents represent content list in ConstructItem, spaces are ignored here:
     *
     * (                    // prefix
     *     $a
     *     &&
     *     in_array(            // prefix
     *         $b
     *         ,
     *         [                    // prefix
     *             $a
     *             ,
     *             $b
     *             &&
     *             $d
     *             ,
     *             get_something(       // prefix
     *                 $t
     *                 &&
     *                 $a
     *             )                    // postfix
     *         ]                    // postfix
     *         ,
     *         true
     *     )                    // postfix
     * )                    // postfix
     *
     * When grouping, simple lists (in the same indentation above) are re-grouped by the separators
     *
     * @param ItemInterface $prefixItem
     * @param string $endTokenValue
     * @param string|null $abortOnToken if this token found before end token, abort item grouping
     * @return ItemInterface|null
     * @throws RuntimeException
     */
    private function groupItems(ItemInterface $prefixItem, string $endTokenValue, $abortOnToken = null)
    {
        $contents = [];

        $token = $prefixItem->lastToken()->nextToken();

        do {
            if ($token->getContent() === $endTokenValue) {
                if (count($contents) > 0) {
                    return $this->buildConstructItem($prefixItem, $contents, $token);
                }
                return new SimpleItemList([$prefixItem, $token]);
            } elseif ($abortOnToken !== null && $token->getContent() === $abortOnToken) {
                return null;
            }

            $item = null;

            if ($token->isGivenKind(T_STRING)) {
                $nextToken = $token->nextToken();
                if ($nextToken->getContent() === '(') {
                    $item = $this->groupItems(new SimpleItemList([$token, $nextToken]), ')');
                }
            } elseif ($token->getContent() === '(') {
                $item = $this->groupItems($token, ')');

            } elseif ($token->getContent() === '[') {
                $item = $this->groupItems($token, ']');

            } elseif ($token->getContent() === '{') {
                $item = $this->groupItems($token, '}');

            } elseif ($token->isGivenKind(T_RETURN) && $endTokenValue !== ';') {
                $item = $this->groupItems($token, ';');

            } elseif ($token->getContent() === '=' && $endTokenValue !== ';') {
                $item = $this->groupItems($token, ';', ')');
            }

            if ($item !== null) {
                $contents[] = $item;
                $token = $item->lastToken();
            } else {
                $contents[] = $token;
            }

            $token = $token->nextToken();

        } while ($token !== null);

        throw new RuntimeException(sprintf('Cannot find end token "%s"', $endTokenValue));
    }

    private function buildConstructItem(ItemInterface $prefixItem, array $contents, ItemInterface $postfixItem)
    {
        $contentList = new SimpleItemList($contents);
        $contentList->setReplaceCallback(function () {
            // we need replacing for internal calls, here we'll just get replaced item returned from method call
        });

        $regroupedItem = $this->regroup($contentList);

        $constructItem = new ComplexItemList();
        $constructItem->addPrefixItem($prefixItem);
        $constructItem->addContentItem($regroupedItem);
        $constructItem->addPostfixItem($postfixItem);

        return $constructItem;
    }

    /**
     * Regoups internal content items into logical groups by operators and separators depending on precedence order
     *
     * @param SimpleItemList $itemList
     * @param bool $wrapIntoComplex
     * @return ComplexItemList
     */
    private function regroup(SimpleItemList $itemList, bool $wrapIntoComplex = false): ComplexItemList
    {
        $separators = explode(
            ' ',
            ', or xor and = += -= *= **= /= .= %= &= |= ^= <<= >>= ?? ? : || && | ^ & == != === !== <> <=> < <= > >= << >> + - . * / % instanceof ** ->'
        );
        foreach ($separators as $separator) {
            $replacedItem = $this->groupSeparatorHelper->regroupListBySeparator($itemList->getItemList(), $separator);
            if ($replacedItem === null) {
                continue;
            }

            foreach ($replacedItem->getContentItems() as $contentItem) {
                if ($contentItem instanceof SimpleItemList) {
                    $this->regroup($contentItem, true);
                }
            }

            if ($wrapIntoComplex) {
                $replacedItem = $this->wrapIntoComplexItemList($replacedItem);
            }

            $itemList->replaceWith($replacedItem);
            return $replacedItem;
        }

        return $this->buildComplexItemList($itemList);
    }

    /**
     * Wrapping is needed to correctly indent separate groups where parentheses are not used. For example:
     *
     * ```
     *     $a
     *     && $b
     * || (
     *     $c
     *     && $d
     * )
     * ```
     *
     * In this case `$a && $b` group is wrapped into this complex item list.
     *
     * @param ItemInterface $item
     * @return ComplexItemList
     */
    private function wrapIntoComplexItemList(ItemInterface $item): ComplexItemList
    {
        $wrappedItem = new ComplexItemList();
        $prefixItem = new EmptyToken();
        $item->firstToken()->insertBefore($prefixItem);
        $wrappedItem->addPrefixItem($prefixItem);
        $wrappedItem->addContentItem($item);
        $postfixItem = new EmptyToken();
        $item->lastToken()->insertAfter($postfixItem);
        $wrappedItem->addPostfixItem($postfixItem);
        return $wrappedItem;
    }

    private function buildComplexItemList(SimpleItemList $contentList): ComplexItemList
    {
        $prefixWhitespace = null;
        $postfixWhitespace = null;

        $contents = $contentList->getItemList();
        $firstToken = reset($contents);
        if ($firstToken instanceof ContextualToken && $firstToken->isWhitespace()) {
            $prefixWhitespace = $firstToken;
            array_shift($contents);

            $firstToken = reset($contents);
            if ($firstToken instanceof ContextualToken && $firstToken->isComment()) {
                array_shift($contents);
                $firstToken = reset($contents);
                if ($firstToken instanceof ContextualToken && $firstToken->isWhitespace()) {
                    $prefixWhitespace = $firstToken;
                    array_shift($contents);
                }
            }
        }
        $lastToken = end($contents);
        if ($lastToken instanceof ContextualToken && $lastToken->isWhitespace()) {
            $postfixWhitespace = $lastToken;
            array_pop($contents);
        }

        $regroupedItem = new ComplexItemList();
        if ($prefixWhitespace !== null) {
            $regroupedItem->addPrefixWhitespaceItem($prefixWhitespace);
        }
        if (count($contents) > 0) {
            $regroupedItem->addContentItemGroup($contents);
        }
        if ($postfixWhitespace !== null) {
            $regroupedItem->addPostfixWhitespaceItem($postfixWhitespace);
        }
        return $regroupedItem;
    }
}
