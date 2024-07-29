<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\LogicalOperatorsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class LogicalOperatorsFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @dataProvider provideCases
     */
    public function testFix(string $expected, string $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideCases(): array
    {
        return [
            [
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 0;
                        $b = 1;
                        $c = 2;
                        $d = 3;
                        
                        if ($a === 0) {
                            return ($a && $b || $c && $d);
                        }
                        
                        if ($b === 1) {
                            return (
                            $a || $b
                            && $c
                            || $d
                            );
                        }
                        
                        if ($c === 2) {
                            return (($a || $b
                            ) && (
                                $c || $d
                            ));
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 0;
                        $b = 1;
                        $c = 2;
                        $d = 3;
                        
                        if ($a === 0) {
                            return ($a and $b or $c and $d);
                        }
                        
                        if ($b === 1) {
                            return (
                            $a or $b
                            and $c
                            or $d
                            );
                        }
                        
                        if ($c === 2) {
                            return (($a or $b
                            ) and (
                                $c or $d
                            ));
                        }
                    }
                }',
            ],
            [
                '<?php if ($a == "foo" && ($b == "bar" || $c == "baz")) {}',
                '<?php if ($a == "foo" AND ($b == "bar" OR $c == "baz")) {}',
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new LogicalOperatorsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_feature_logical_operators';
    }
}
