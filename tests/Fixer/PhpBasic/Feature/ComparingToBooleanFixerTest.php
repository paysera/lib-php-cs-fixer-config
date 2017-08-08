<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ComparingToBooleanFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class ComparingToBooleanFixerTest extends AbstractFixerTestCase
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
                if(!$valid) {
                    return $valid;
                }','<?php
                if($valid === false) {
                    return $valid;
                }',
            ],
            [
                '<?php
                if((bool)$valid) {
                    return $valid;
                }','<?php
                if($valid === true) {
                    return $valid;
                }',
            ],
            [
                '<?php return !$valid || !$something;',
                '<?php return $valid === false || $something !== true;',
            ],
            [
                '<?php return (bool)$valid && !$something;',
                '<?php return $valid === true && $something === false;',
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
