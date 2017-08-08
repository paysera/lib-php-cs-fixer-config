<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ConditionResultsFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class ConditionResultsFixerTest extends AbstractFixerTestCase
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
                '<?php $a = !($d && $e);',
                '<?php $a = $d && $e ? false : true;',
            ],
            [
                '<?php return !($a && $b);',
                '<?php return $a && $b ? false : true;',
            ],
            [
                '<?php return (bool)($d && $e);',
                '<?php return $d && $e ? true : false;',
            ],
            [
                '<?php
                
                $a = (bool)($d && $e);
                return (bool)($a && $b);',
                '<?php
                
                $a = $d && $e ? true : false;
                if ($a && $b) {
                    return true;
                }
                
                return false;',
            ],
            [
                '<?php
                $a = true;
                $b = true;
                
                return !($a && $b);',
                '<?php
                $a = true;
                $b = true;
                
                if ($a && $b) {
                    return false;
                }
                
                return true;',
            ],
            [
                '<?php
                $a = true;
                $b = true;
                
                return !($a && $b);',
                '<?php
                $a = true;
                $b = true;
                
                if ($a && $b) {
                    return false;
                } else {
                    return true;
                }',
            ],
            [
                '<?php
                return !($this->someFunction($a, $b, [1, 2]));',
                '<?php
                if ($this->someFunction($a, $b, [1, 2])) {
                    return false;
                }
                return true;',
            ],

        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ConditionResultsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_condition_results';
    }
}
