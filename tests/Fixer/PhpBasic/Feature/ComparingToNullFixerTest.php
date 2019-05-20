<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ComparingToNullFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ComparingToNullFixerTest extends AbstractPayseraFixerTestCase
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
                    private function sampleFunction(Something $something = null)
                    {
                        if ($a || $b && $c && $something === null || ($a && $b)) {
                    
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    private function sampleFunction(Something $something = null)
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
            [
                '<?php
                class Sample
                {
                    private function func(bool $value = null)
                    {
                        return ($value && func());
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample extends B
                {
                    private function func(bool $value)
                    {
                        if ($value && $this->whitespaceAfter !== null) {
                            $this->doStuff();
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample extends B
                {
                    private function func($value)
                    {
                        if ($value && $this->whitespaceAfter !== null) {
                            $this->doStuff();
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample extends B
                {
                    /**
                     * @param Entity $value
                     */
                    private function func($value)
                    {
                        if ($value && $this->whitespaceAfter !== null) {
                            $this->doStuff();
                        }
                    }
                }',
                null,
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
