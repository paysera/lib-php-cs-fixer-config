<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Basic;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Basic\SingleClassPerFileFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class SingleClassPerFileFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @param string $expected
     * @param string|null $input
     *
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

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new SingleClassPerFileFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_basic_single_class_per_file';
    }
}
