<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ComparingToNullFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class ComparingToNullFixerTest extends AbstractFixerTestCase
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
                '<?php
                class Sample
                {
                    private function sampleFunction(Something $something)
                    {
                        if ($a || $b && $c && $something === null || ($a && $b)) {
                    
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    private function sampleFunction(Something $something)
                    {
                        if ($a || $b && $c && !$something || ($a && $b)) {
                    
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction(Something $something = null)
                    {
                        if ($something !== null && $a) {
                    
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    private function sampleFunction(Something $something = null)
                    {
                        if ($something && $a) {
                    
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction(Something $something)
                    {
                        if ($a || $b && $this->someFunction($something)) {
                    
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction($something = null)
                    {
                        if ($something) {
                    
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction(BlackListEntry $blacklistEntry = null)
                    {
                        if ($blacklistEntry->getEntry() !== null) {
                            return $blacklistEntry->getEntry();
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function func(ValueClass $value = null)
                    {
                        return $value !== null ? $value->getSomething() : null;
                    }
                }',
                '<?php
                class Sample
                {
                    private function func(ValueClass $value = null)
                    {
                        return $value ? $value->getSomething() : null;
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ComparingToNullFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_comparing_to_null';
    }
}
