<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ClassConstructorsFixerTest extends AbstractPayseraFixerTestCase
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

    public function getFixerName(): string
    {
        return 'new_with_braces';
    }
}
