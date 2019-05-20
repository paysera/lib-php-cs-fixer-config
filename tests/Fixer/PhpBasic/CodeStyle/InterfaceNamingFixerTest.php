<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\InterfaceNamingFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class InterfaceNamingFixerTest extends AbstractPayseraFixerTestCase
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

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new InterfaceNamingFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_interface_naming';
    }
}
