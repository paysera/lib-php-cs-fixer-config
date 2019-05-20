<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

interface ItemInterface
{
    public function lastToken(): ContextualToken;

    public function firstToken(): ContextualToken;

    /**
     * @return ComplexItemList[]
     */
    public function getComplexItemLists(): array;

    /**
     * @return bool
     */
    public function isSplitIntoSeveralLines(): bool;

    /**
     * Without typehint to be compatible with Token
     *
     * @return string
     */
    public function getContent();

    /**
     * Whether this item equals another in means of structure and content
     *
     * @param ItemInterface $item
     * @return bool
     */
    public function equalsToItem(ItemInterface $item): bool;
}
