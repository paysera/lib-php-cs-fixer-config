<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class FunctionIsNullFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php return null !== $a || $b;',
                '<?php return !is_null($a || $b);',
            ],
            [
                '<?php return null === $a;',
                '<?php return is_null($a);',
            ],
            [
                '<?php return null !== $this->someFunction($a, $b);',
                '<?php return !is_null($this->someFunction($a, $b));',
            ],
            [
                '<?php return null === ($a || $b) && $c;',
                '<?php return is_null(($a || $b) && $c);',
            ],
            [
                '<?php return null !== null;',
                '<?php return !is_null(null);',
            ],
            [
                '<?php return null !== 1;',
                '<?php return !is_null(1);',
            ],
        ];
    }

    public function getFixerName(): string
    {
        return 'is_null';
    }
}
