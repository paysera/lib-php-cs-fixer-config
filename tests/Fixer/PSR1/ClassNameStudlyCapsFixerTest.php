<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PSR1;

use Paysera\PhpCsFixerConfig\Fixer\PSR1\ClassNameStudlyCapsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ClassNameStudlyCapsFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php class SomeCustomClassName {}',
                '<?php class some_Custom_class_name {}',
            ], [
                '<?php class InvalidClassName {}',
                '<?php class invalid_className {}',
            ], [
                '<?php class AnotherInvalidClassName {}',
                '<?php class another_invalid_class_name {}',
            ], [
                '<?php class SomeClassName651 {}',
                '<?php class SomeClass_name_651 {}',
            ], [
                '<?php class SampleOfClassName {}',
                '<?php class sampleOf_className {}',
            ], [
                '<?php class SampleOfClassName {
                    const SAMPLE_CONSTANT = 0;
                    private function sampleFunction(){
                        $constant = SampleOfClassName::SAMPLE_CONSTANT;
                    }
                }',
                '<?php class sampleOf_className {
                    const SAMPLE_CONSTANT = 0;
                    private function sampleFunction(){
                        $constant = sampleOf_className::SAMPLE_CONSTANT;
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ClassNameStudlyCapsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/psr_1_class_name_studly_caps';
    }
}
