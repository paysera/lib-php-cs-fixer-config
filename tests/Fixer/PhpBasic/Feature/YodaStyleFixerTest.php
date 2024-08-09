<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class YodaStyleFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @dataProvider provideCases
     */
    public function testFix(string $expected, string $input = null)
    {
        $this->fixer->configure(['equal' => false, 'identical' => false, 'less_and_greater' => false]);
        $this->doTest($expected, $input);
    }

    public function provideCases(): array
    {
        return [
            [
                '<?php return $a !== null || $b;',
                '<?php return null !== $a || $b;',
            ],
            [
                '<?php return $a === null;',
                '<?php return null === $a;',
            ],
            [
                '<?php return $this->someFunction($a, $b) !== null;',
                '<?php return null !== $this->someFunction($a, $b);',
            ],
            [
                '<?php return $a === null || $b && $c;',
                '<?php return null === $a || $b && $c;',
            ],
//            [
//                '<?php return 1 !== null;',
//                '<?php return null !== 1;',
//            ],
        ];
    }

    public function getFixerName(): string
    {
        return 'yoda_style';
    }
}
