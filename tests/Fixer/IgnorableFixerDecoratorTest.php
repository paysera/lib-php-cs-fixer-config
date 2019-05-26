<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer;

use Exception;
use SplFileInfo;
use Paysera\PhpCsFixerConfig\Fixer\IgnorableFixerDecorator;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use Paysera\PhpCsFixerConfig\Tests\Fixtures\ReplaceContentsFixer;

class IgnorableFixerDecoratorTest extends AbstractPayseraFixerTestCase
{
    /**
     * @param string $expected
     * @param string|null $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $filename = __DIR__ . '/tmp/input.php';
        if (file_put_contents($filename, $input ?? $expected) === false) {
            throw new Exception('Cannot write temporary file');
        }
        try {
            $this->doTest($expected, $input, new SplFileInfo($filename));
        } finally {
            unlink($filename);
        }
    }

    public function provideFixCases()
    {
        return [
            [
                '<?php // replaced',
                '<?php // to be replaced',
            ],
            [
                '<?php // this will not be replaced
                // @php-cs-fixer-ignore my_test_fixer
                ',
            ],
        ];
    }

    protected function createFixer()
    {
        return new IgnorableFixerDecorator(new ReplaceContentsFixer());
    }

    protected function getFixerName()
    {
        return 'my_test_fixer';
    }
}
