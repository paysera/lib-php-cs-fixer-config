<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\StrictComparisonOperatorsFixer;
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
                '<?php return in_array($a, $ab, true);',
                '<?php return in_array($a, $ab, false);',
            ],
            [
                '<?php return in_array($b, [0, 1, 3], true);',
                '<?php return in_array($b, [0, 1, 3]);',
            ],
            [
                '<?php if ($a !== "foo" && ($b === "bar" || $c !== "baz")) {}',
                '<?php if ($a != "foo" && ($b == "bar" || $c != "baz")) {}',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new StrictComparisonOperatorsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_strict_comparison_operators';
    }
}
