<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class StrictComparisonOperatorsFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php if ($a !== "foo" && ($b === "bar" || $c !== "baz")) {}',
                '<?php if ($a != "foo" && ($b == "bar" || $c != "baz")) {}',
            ],
        ];
    }

    public function getFixerName(): string
    {
        return 'strict_comparison';
    }
}
