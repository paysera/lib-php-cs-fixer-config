<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

class SecurityContext implements SecurityContextInterface
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function getToken()
    {
        return $this->tokenStorage->getToken();
    }
}
