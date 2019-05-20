<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures;

interface SecurityContextInterface extends TokenStorageInterface
{
    const SOMETHING = 'Something';
}
