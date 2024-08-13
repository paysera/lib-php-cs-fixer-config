<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\PropertyNamingFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class PropertyNamingFixerTest extends AbstractPayseraFixerTestCase
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
                    protected $isValid; // TODO: "$isValid" - PhpBasic convention 2.5.4: We do not use verbs or questions for property names
                    protected $check; // TODO: "$check" - PhpBasic convention 2.5.4: We do not use verbs or questions for property names
                }',
                '<?php
                class Sample
                {
                    protected $isValid;
                    protected $check;
                }'
            ],
            [
                '<?php
                class Sample
                {
                    protected $hasCancelPermission = false; // TODO: "$hasCancelPermission" - PhpBasic convention 2.5.4: We do not use verbs or questions for property names
                    protected $hasSignPermission = false; // TODO: "$hasSignPermission" - PhpBasic convention 2.5.4: We do not use verbs or questions for property names
                }',
                '<?php
                class Sample
                {
                    protected $hasCancelPermission = false;
                    protected $hasSignPermission = false;
                }'
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new PropertyNamingFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_code_style_property_naming';
    }
}
