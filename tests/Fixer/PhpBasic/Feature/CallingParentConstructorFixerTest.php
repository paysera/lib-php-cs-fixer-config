<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\CallingParentConstructorFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class CallingParentConstructorFixerTest extends AbstractFixerTestCase
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
                class Sample
                {
                    public function __construct($arg1, $arg2)
                    {
                        parent::__construct($arg1);
                        $this->setArg2($arg2);
                    }
                }',
                '<?php
                class Sample
                {
                    public function __construct($arg1, $arg2)
                    {
                        $this->setArg2($arg2);
                        parent::__construct($arg1);
                    }
                }'
            ],
            [
                '<?php
                class Sample
                {
                    public function __construct($arg1, $arg2)
                    {
                        parent::__construct($arg1,
                            $arg2, $arg3,
                            $arg4
                        );
                        $this->setArg2($arg2);
                        $this->setArg3($arg2);
                        $this->setArg4($arg2);
                    }
                }',
                '<?php
                class Sample
                {
                    public function __construct($arg1, $arg2)
                    {
                        $this->setArg2($arg2);
                        $this->setArg3($arg2);
                        parent::__construct($arg1,
                            $arg2, $arg3,
                            $arg4
                        );
                        $this->setArg4($arg2);
                    }
                }'
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new CallingParentConstructorFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_calling_parent_constructor';
    }
}
