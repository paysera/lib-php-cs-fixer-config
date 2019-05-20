<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\UnnecessaryStructuresFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class UnnecessaryStructuresFixerTest extends AbstractPayseraFixerTestCase
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

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new UnnecessaryStructuresFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_unnecessary_structures';
    }
}
