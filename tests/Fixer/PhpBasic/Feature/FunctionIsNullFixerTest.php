<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use PhpCsFixer\Test\AbstractFixerTestCase;

final class FunctionIsNullFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string $expected
     * @param null|string $input
     *
     * @dataProvider provideCases
     */
    public function testFix($expected, $input = null)
    {
        $this->fixer->configure(['use_yoda_style' => false]);
        $this->doTest($expected, $input);
    }

    public function provideCases()
    {
        return [
            [
                '<?php return $a || $b !== null;',
                '<?php return !is_null($a || $b);',
            ],
            [
                '<?php return $a === null;',
                '<?php return is_null($a);',
            ],
            [
                '<?php return $this->someFunction($a, $b) !== null;',
                '<?php return !is_null($this->someFunction($a, $b));',
            ],
            [
                '<?php return ($a || $b) && $c === null;',
                '<?php return is_null(($a || $b) && $c);',
            ],
            [
                '<?php return null !== null;',
                '<?php return !is_null(null);',
            ],
            [
                '<?php return 1 !== null;',
                '<?php return !is_null(1);',
            ],
        ];
    }

    public function getFixerName()
    {
        return 'is_null';
    }
}
