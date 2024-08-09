<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\UnnecessaryStructuresFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class UnnecessaryStructuresFixerTest extends AbstractPayseraFixerTestCase
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
                if ($first || $a) {
                    if ($second && $b || $a) { // TODO: "if" - PhpBasic convention 3.10: We avoid unnecessary structures
                        return true;
                    }
                }',
                '<?php
                if ($first || $a) {
                    if ($second && $b || $a) {
                        return true;
                    }
                }'
            ],
            [
                '<?php
                if ($first) {
                    if ($second) { // TODO: "if" - PhpBasic convention 3.10: We avoid unnecessary structures
                        if ($third) { // TODO: "if" - PhpBasic convention 3.10: We avoid unnecessary structures
                            return true;
                        }
                    }
                }',
                '<?php
                if ($first) {
                    if ($second) {
                        if ($third) {
                            return true;
                        }
                    }
                }'
            ],
            [
                '<?php
                if ($first) {
                    if ($second) { // TODO: "if" - PhpBasic convention 3.10: We avoid unnecessary structures
                        if ($third) {
                            return true;
                        }
                        return false;
                    }
                }',
                '<?php
                if ($first) {
                    if ($second) {
                        if ($third) {
                            return true;
                        }
                        return false;
                    }
                }'
            ],
            [
                '<?php
                if ($curlyBraceStartIndex !== null) {
                    $curlyBraceEndIndex = $tokens->findBlockEnd(
                        Tokens::BLOCK_TYPE_CURLY_BRACE,
                        $curlyBraceStartIndex
                    );
                    if ($curlyBraceEndIndex === $childEndCurlyBraceIndex) {
                        $this->insertComment($tokens, $curlyBraceStartIndex);
                    }
                }'
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new UnnecessaryStructuresFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_feature_unnecessary_structures';
    }
}
