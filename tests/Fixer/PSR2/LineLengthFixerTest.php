<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PSR2;

use Paysera\PhpCsFixerConfig\Fixer\PSR2\LineLengthFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class LineLengthFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @param string $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->fixer->configure(['soft_limit' => 80, 'hard_limit' => 119]);
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return [
            [
                '<?php
function main($a)
{
    if ($a) {
        // todo: following line exceeds 119 characters
        $c = "And" . "some" . "another" . "a" . "bit" . "more" . "longer" . "soft" . "limit" . "exceeding" ."string" . "for" . "testing";
    }
}
',
                '<?php
function main($a)
{
    if ($a) {
        $c = "And" . "some" . "another" . "a" . "bit" . "more" . "longer" . "soft" . "limit" . "exceeding" ."string" . "for" . "testing";
    }
}
',
            ],
            [
                '<?php
                $a = "Some really long string. Some really long string. Some really long string. Some really long string.";
                ',
                null,
            ],
        ];
    }

    protected function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new LineLengthFixer(),
        ]);
        return $fixerFactory;
    }

    protected function getFixerName()
    {
        return 'Paysera/psr_2_line_length';
    }
}
