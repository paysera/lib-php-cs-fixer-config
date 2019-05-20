<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PSR1;

use Paysera\PhpCsFixerConfig\Fixer\PSR1\ClassConstantUpperCaseFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ClassConstantUpperCaseFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @param string $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [
            [
                '<?php class Sample {
                    const SOME_CONSTANTSAMPLE = 0;
                }',
                '<?php class Sample {
                    const some_constantSample = 0;
                }',
            ], [
                '<?php class Sample {
                    const NORMAL_CONSTANT = 1;
                }',
                '<?php class Sample {
                    const NORMAL_constant = 1;
                }',
            ], [
                '<?php class Sample {
                    const CONSTANT_N_AME = 1;
                }',
                '<?php class Sample {
                    const cONstANt_N_AMe = 1;
                }',
            ], [
                '<?php class Sample {
                    const CONSTANTNAME = 2;
                }',
                '<?php class Sample {
                    const constantname = 2;
                }',
            ], [
                '<?php class Sample {
                    const SOME_CONSTANT_SAMPLE = 2;
                    private function sampleFunction(){
                        $constant = self::SOME_CONSTANT_SAMPLE;
                    }
                }',
                '<?php class Sample {
                    const some_constant_Sample = 2;
                    private function sampleFunction(){
                        $constant = self::some_constant_Sample;
                    }
                }',
            ]
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ClassConstantUpperCaseFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/psr_1_class_constant_upper_case';
    }
}
