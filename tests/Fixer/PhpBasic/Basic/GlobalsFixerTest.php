<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Basic;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Basic\GlobalsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class GlobalsFixerTest extends AbstractPayseraFixerTestCase
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
                $a = 2; // TODO: "$a" - PhpBasic convention 1.2: We do not use global variables, constants and functions.
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 0;
                        $b = 1;
                        $c = 2;
                    }
                }',
                '<?php
                $a = 2;
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 0;
                        $b = 1;
                        $c = 2;
                    }
                }',
            ],
            [
                '<?php
                function globalFunction(){
                    return 0;
                } // TODO: "function" - PhpBasic convention 1.2: We do not use global variables, constants and functions.
                const GLOBAL_CONSTANT = 1; // TODO: "const" - PhpBasic convention 1.2: We do not use global variables, constants and functions.
                $GLOBALS["a"] = "a"; // TODO: "$GLOBALS" - PhpBasic convention 1.2: We do not use global variables, constants and functions.
                global $variable; // TODO: "global" - PhpBasic convention 1.2: We do not use global variables, constants and functions.
                $variable = 1; // TODO: "$variable" - PhpBasic convention 1.2: We do not use global variables, constants and functions.

                class Sample
                {
                    public function sampleFunction()
                    {
                        global $variable;
                        $globalVar = $variable;
                        $globalVariable = $GLOBALS["a"];
                        $globalFunction = globalFunction();
                        $globalConstant = GLOBAL_CONSTANT;
                    }
                }',
                '<?php
                function globalFunction(){
                    return 0;
                }
                const GLOBAL_CONSTANT = 1;
                $GLOBALS["a"] = "a";
                global $variable;
                $variable = 1;

                class Sample
                {
                    public function sampleFunction()
                    {
                        global $variable;
                        $globalVar = $variable;
                        $globalVariable = $GLOBALS["a"];
                        $globalFunction = globalFunction();
                        $globalConstant = GLOBAL_CONSTANT;
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new GlobalsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_basic_globals';
    }
}
