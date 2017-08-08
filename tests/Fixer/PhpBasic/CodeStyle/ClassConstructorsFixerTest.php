<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ClassConstructorsFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class ClassConstructorsFixerTest extends AbstractFixerTestCase
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
                    private function sampleFunction()
                    {
                        $restrictionComment = (new RestrictionComment())
                            ->setComment($data->get("comment"))
                        ;
                    }
                }'
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $value = new Sample();
                    }
                }',
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $value = new Sample;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $value = new SomeNamespace\Sample\SomeClass();
                        $secondValue = new \SomeNamespace\With\Root\Sample\SomeClass();
                    }
                }',
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $value = new SomeNamespace\Sample\SomeClass;
                        $secondValue = new \SomeNamespace\With\Root\Sample\SomeClass;
                    }
                }'
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ClassConstructorsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_class_constructors';
    }
}
