<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Basic;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Basic\SingleClassPerFileFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class SingleClassPerFileFixerTest extends AbstractFixerTestCase
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
class ClassOne
{
}
class ClassTwo
{
}
// TODO: "class" - PhpBasic convention 1.3: Only one class/interface can be declared per file',
                '<?php
class ClassOne
{
}
class ClassTwo
{
}'
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new SingleClassPerFileFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_basic_single_class_per_file';
    }
}
