<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class StrictComparisonParameterFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php return in_array($a, $this->someFunction($some, $variables, [0, 1, 2, 3]), true);',
                '<?php return in_array($a, $this->someFunction($some, $variables, [0, 1, 2, 3]));',
            ],
            [
                '<?php return in_array($this->someFunction(), [0, 1, 2], $this->someStrictFunction());',
            ],
            [
                '<?php return in_array($this->someFunction($some, $variables), [0, 1, 2], $someStrictVariable);',
            ],
            [
                '<?php return in_array($b, [0, 1, 3], true);',
                '<?php return in_array($b, [0, 1, 3]);',
            ],
        ];
    }

    public function getFixerName(): string
    {
        return 'strict_param';
    }
}
