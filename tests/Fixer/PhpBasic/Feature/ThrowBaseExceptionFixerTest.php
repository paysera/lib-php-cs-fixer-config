<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ThrowBaseExceptionFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ThrowBaseExceptionFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php throw new \Exception(); // TODO: PhpBasic convention 3.20.1: We almost never throw base \Exception class',
                '<?php throw new \Exception();',
            ],
            [
                '<?php throw new Exception(); // TODO: PhpBasic convention 3.20.1: We almost never throw base \Exception class',
                '<?php throw new Exception();',
            ],
            [
                '<?php
                try {
                } catch(\Exception $exception) {
                    throw new \Exception(); // TODO: PhpBasic convention 3.20.1: We almost never throw base \Exception class
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ThrowBaseExceptionFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_throw_base_exception';
    }
}
