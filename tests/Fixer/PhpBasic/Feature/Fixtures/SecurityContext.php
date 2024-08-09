<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

class SecurityContext implements SecurityContextInterface
{
    private TokenStorageInterface $tokenStorage;

    public function getToken()
    {
        return $this->tokenStorage->getToken();
    }
}
