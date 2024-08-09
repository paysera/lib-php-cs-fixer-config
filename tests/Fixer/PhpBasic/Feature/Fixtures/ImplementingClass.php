<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

use SplFileInfo;

class ImplementingClass implements DenormalizerInterface
{
    /**
     * @param SplFileInfo $something
     */
    public function make($something)
    {
        $something->getType();
    }
}
