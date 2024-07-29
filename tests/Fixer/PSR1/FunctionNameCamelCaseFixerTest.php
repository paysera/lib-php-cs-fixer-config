<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PSR1;

use Paysera\PhpCsFixerConfig\Fixer\PSR1\FunctionNameCamelCaseFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class FunctionNameCamelCaseFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @dataProvider provideFixCases
     */
    public function testFix(string $expected, string $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases(): array
    {
        return [
            [
                '<?php class Sample {
                    public function __construct(){}
                }',
                null,
            ],
            [
                '<?php class Sample {
                    private function someInvalidFunction(){}
                }',
                '<?php class Sample {
                    private function Some_InvalidFunction(){}
                }',
            ],
            [
                '<?php class Sample {
                    private function someInvalidFunction(){}
                }',
                '<?php class Sample {
                    private function some_invalid_function(){}
                }',
            ],
            [
                '<?php class Sample {
                    private function sOMEINVALIDFUNCTION(){}
                }',
                '<?php class Sample {
                    private function SOMEINVALID_FUNCTION(){}
                }',
            ],
            [
                '<?php class Sample {
                    private function sampleFunction() {
                        $function = $this->someInvalidFunction();
                    }
                    private function someInvalidFunction(){}
                }',
                '<?php class Sample {
                    private function Sample_function() {
                        $function = $this->Some_invalid_function();
                    }
                    private function Some_invalid_function(){}
                }',
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new FunctionNameCamelCaseFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/psr_1_function_name_camel_case';
    }
}
