<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class StrictComparisonOperatorsFixerTest extends AbstractPayseraFixerTestCase
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
