<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

class ImplementingClass implements DenormalizerInterface
{
    /**
     * @param \SplFileInfo $something
     */
    public function make($something)
    {
        $something->getType();
    }
}
