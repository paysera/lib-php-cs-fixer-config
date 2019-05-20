<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

class TypedItem
{
    const TYPE_PREFIX = 'prefix';
    const TYPE_PREFIX_WHITESPACE = 'prefix_whitespace';
    const TYPE_CONTENT = 'content';
    const TYPE_SEPARATOR = 'separator';
    const TYPE_SEPARATOR_AFTER_CONTENTS = 'separator_after_contents';
    const TYPE_POSTFIX_WHITESPACE = 'postfix_whitespace';
    const TYPE_POSTFIX = 'postfix';

    private $item;

    private $type;

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
