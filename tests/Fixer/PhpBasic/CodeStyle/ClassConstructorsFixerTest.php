<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

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

    public function getFixerName()
    {
        return 'new_with_braces';
    }
}
