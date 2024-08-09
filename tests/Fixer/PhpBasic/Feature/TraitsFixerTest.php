<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TraitsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class TraitsFixerTest extends AbstractPayseraFixerTestCase
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
// TODO: "TraitOne" - PhpBasic convention 3.23: We do not use traits
trait TraitOne
{
}',
                '<?php
trait TraitOne
{
}'
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new TraitsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_feature_traits';
    }
}
