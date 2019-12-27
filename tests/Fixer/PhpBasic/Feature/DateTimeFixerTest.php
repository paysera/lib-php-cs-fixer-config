<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\DateTimeFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class DateTimeFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php $otherDate = date("l jS \of F Y h:i:s A"); // TODO: "date" - PhpBasic convention 3.19: Use \\DateTime object instead',
                '<?php $otherDate = date("l jS \of F Y h:i:s A");',
            ],
            [
                '<?php $someDate = date("l"); // TODO: "date" - PhpBasic convention 3.19: Use \\DateTime object instead',
                '<?php $someDate = date("l");',
            ],
            [
                '<?php
                if ($something == date("l")) { // TODO: "date" - PhpBasic convention 3.19: Use \\DateTime object instead
                    if (time() === "something") { // TODO: "time" - PhpBasic convention 3.19: Use \\DateTime object instead
                        return date_create(); // TODO: "date_create" - PhpBasic convention 3.19: Use \\DateTime object instead
                    }
                }',
                '<?php
                if ($something == date("l")) {
                    if (time() === "something") {
                        return date_create();
                    }
                }'
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new DateTimeFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_date_time';
    }
}
