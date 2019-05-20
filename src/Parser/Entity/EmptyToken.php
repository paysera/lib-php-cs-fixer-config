<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

class EmptyToken extends ContextualToken implements ItemInterface
{
    public function __construct()
    {
        parent::__construct('');
    }

    public function isWhitespace($whitespaces = " \t\n\r\0\x0B")
    {
        return false;
    }
}
