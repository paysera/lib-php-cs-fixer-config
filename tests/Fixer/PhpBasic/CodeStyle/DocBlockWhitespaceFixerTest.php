<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\DocBlockWhitespaceFixer;
use PhpCsFixer\FixerFactory;

final class DocBlockWhitespaceFixerTest extends AbstractPayseraFixerTestCase
{
    /**
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
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param $locationId
                     * @param $location
                     */
                    public function updateLocation($locationId, $location)
                    {
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param                                                      $locationId
                     * @param       $location
                     */
                    public function updateLocation($locationId, $location)
                    {
                    }
                }',
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param $locationId
                     * @param Object|null $location
                     */
                    public function updateLocation($locationId, $location)
                    {
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param             $locationId
                     * @param Object|null $location
                     */
                    public function updateLocation($locationId, $location)
                    {
                    }
                }',
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param Object|null $locationId
                     * @param Object $location
                     * @param $city
                     */
                    public function updateLocation($locationId, $location, $city)
                    {
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param Object|null $locationId
                     * @param Object      $location
                     * @param             $city
                     */
                    public function updateLocation($locationId, $location, $city)
                    {
                    }
                }',
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * This is long description with complex spacing:
                     *    - some item
                     *        - some sub-item
                     *
                     *              Do not care about descriptions
                     *
                     * @param Object|null $locationId
                     * @param Object $location
                     * @param $city
                     */
                    public function updateLocation($locationId, $location, $city)
                    {
                    }
                }',
                null,
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new DocBlockWhitespaceFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_code_style_doc_block_whitespace';
    }
}
