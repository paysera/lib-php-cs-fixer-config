<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PSR1;

use Paysera\PhpCsFixerConfig\Fixer\PSR1\FileSideEffectsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class FileSideEffectsFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php
                // side effect: change ini settings
                ini_set("error_reporting", E_ALL);
                 
                // side effect: loads a file
                include "file.php";
                
                // side effect: outputs data to console
                $a = 1;
                var_dump($a);
                echo $a;
                print_r($a);
                ',
            ],
            [
                '<?php 
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 0;
                    }
                }',
            ],
            [
                '<?php
                // side effect: change ini settings
                ini_set("error_reporting", E_ALL);
                 
                // side effect: loads a file
                include "file.php";
                
                // side effect: outputs data to console
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 1;
                        var_dump($a);
                        echo $a;
                        print_r($a);
                    }
                }
/* TODO: A file should declare new symbols (classes, functions, constants, etc.)
    and cause no other side effects, or it should execute logic with side effects, but should not do both. */',
                '<?php
                // side effect: change ini settings
                ini_set("error_reporting", E_ALL);
                 
                // side effect: loads a file
                include "file.php";
                
                // side effect: outputs data to console
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 1;
                        var_dump($a);
                        echo $a;
                        print_r($a);
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new FileSideEffectsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/psr_1_file_side_effects';
    }
}
