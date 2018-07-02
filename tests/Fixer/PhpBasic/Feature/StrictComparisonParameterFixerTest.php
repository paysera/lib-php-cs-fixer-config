<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use PhpCsFixer\Test\AbstractFixerTestCase;

final class StrictComparisonParameterFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string $expected
     * @param null|string $input
     *
     * @dataProvider provideCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideCases()
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

    public function getFixerName()
    {
        return 'strict_param';
    }
}
