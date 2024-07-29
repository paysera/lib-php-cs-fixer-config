<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\InterfaceNamingFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class InterfaceNamingFixerTest extends AbstractPayseraFixerTestCase
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
                interface SampleInterface
                {
                    
                }',
                '<?php
                interface Sample
                {
                    
                }',
            ],
            [
                '<?php
                interface SomeValidInterface
                {
                    
                }',
                null,
            ],
            [
                '<?php
                interface MoreUrlBuilderInterface
                {
                    
                }',
                '<?php
                interface MoreUrlBuilder
                {
                    
                }',
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new InterfaceNamingFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_code_style_interface_naming';
    }
}
