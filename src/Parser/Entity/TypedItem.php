<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

class TypedItem
{
    public const TYPE_PREFIX = 'prefix';
    public const TYPE_PREFIX_WHITESPACE = 'prefix_whitespace';
    public const TYPE_CONTENT = 'content';
    public const TYPE_SEPARATOR = 'separator';
    public const TYPE_SEPARATOR_AFTER_CONTENTS = 'separator_after_contents';
    public const TYPE_POSTFIX_WHITESPACE = 'postfix_whitespace';
    public const TYPE_POSTFIX = 'postfix';

    private ItemInterface $item;

    private string $type;

    public function __construct(ItemInterface $item, string $type)
    {
        $this->item = $item;
        $this->type = $type;
    }

    public function getItem(): ItemInterface
    {
        return $this->item;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
