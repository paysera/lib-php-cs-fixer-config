<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class FunctionCountFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @dataProvider provideCases
     */
    public function testFix(string $expected, string $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideCases(): array
    {
        return [
            [
                '<?php return count([1, 2, 3, 4, 5]);',
                '<?php return sizeof([1, 2, 3, 4, 5]);',
            ],
            [
                '<?php $a = []; return count($a);',
                '<?php $a = []; return sizeof($a);',
            ],
            [
                '<?php count([]); count([]);',
                '<?php sizeof([]); count([]);',
            ],
        ];
    }

    public function getFixerName(): string
    {
        return 'no_alias_functions';
    }
}
