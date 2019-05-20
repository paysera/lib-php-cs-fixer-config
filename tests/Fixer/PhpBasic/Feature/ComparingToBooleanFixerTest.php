<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ComparingToBooleanFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ComparingToBooleanFixerTest extends AbstractPayseraFixerTestCase
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
                if ($valid === false) {
                    return $valid;
                }',
                null,
            ],
            [
                '<?php
                if ($valid === true) {
                    return $valid;
                }',
                null,
            ],
            [
                '<?php return $valid === false || $something !== true;',
                null,
            ],
            [
                '<?php return $valid === true && $something === false;',
                null,
            ],
            'Corrects when type is known from method arguments' => [
                '<?php
                function a(bool $valid, bool $something) {
                    return $valid && !$something;
                }',
                '<?php
                function a(bool $valid, bool $something) {
                    return $valid === true && $something === false;
                }',
            ],
            'Corrects with not identical operators' => [
                '<?php
                function a(bool $valid, bool $something) {
                    return !$valid && $something;
                }',
                '<?php
                function a(bool $valid, bool $something) {
                    return $valid !== true && $something !== false;
                }',
            ],
            [
                '<?php
                /**
                 * @param bool $valid
                 * @param bool|null $something
                 */
                function a($valid, $something) {
                    return !$valid && $something !== false;
                }',
                '<?php
                /**
                 * @param bool $valid
                 * @param bool|null $something
                 */
                function a($valid, $something) {
                    return $valid !== true && $something !== false;
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ComparingToBooleanFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_comparing_to_boolean';
    }
}
