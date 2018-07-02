<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use PhpCsFixer\Test\AbstractFixerTestCase;

final class StrictComparisonOperatorsFixerTest extends AbstractFixerTestCase
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
                '<?php if ($a !== "foo" && ($b === "bar" || $c !== "baz")) {}',
                '<?php if ($a != "foo" && ($b == "bar" || $c != "baz")) {}',
            ],
        ];
    }

    public function getFixerName()
    {
        return 'strict_comparison';
    }
}
