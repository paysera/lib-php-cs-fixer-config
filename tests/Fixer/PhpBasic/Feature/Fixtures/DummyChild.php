<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

class DummyChild extends DummyParent
{
    /**
     * Sets application
     *
     * @param \ArrayAccess $application
     */
    public function setApplication($application)
    {
        $this->application = $application;
    }
}
