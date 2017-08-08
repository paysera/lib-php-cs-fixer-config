<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PSR2;

use Paysera\PhpCsFixerConfig\Fixer\PSR2\LineLengthFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class LineLengthFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->fixer->configure(['soft_limit' => 110]);
        $this->doTest($expected, $input, new \SplFileInfo('tests/Fixer/PSR2/Fixtures/temp.php'));
    }

    public function provideFixCases()
    {
        return [
            [
                '<?php

class Sample
{
    private function sampleFunction()
    {
        $a = 0;
        $b = "Some" . "long" . "but" . "not" . "so" . "long" . "hard" . "limit" ."string" . "for" . "test";
        $c = "And" . "some" . "another" . "a" . "bit" . "more" . "longer" . "soft" . "limit" . "exceeding" ."string" . "for" . "testing";
    }
}
// TODO: Line (9) exceeds SOFT_LIMIT of 110 characters; contains 137 characters',
                '<?php

class Sample
{
    private function sampleFunction()
    {
        $a = 0;
        $b = "Some" . "long" . "but" . "not" . "so" . "long" . "hard" . "limit" ."string" . "for" . "test";
        $c = "And" . "some" . "another" . "a" . "bit" . "more" . "longer" . "soft" . "limit" . "exceeding" ."string" . "for" . "testing";
    }
}',
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
