<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\CallingParentConstructorFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class CallingParentConstructorFixerTest extends AbstractPayseraFixerTestCase
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
                class Sample extends ParentSample
                {
                    public function __construct($arg1, $arg2)
                    {
                        parent::__construct($arg1);
                        $this->setArg2($arg2);
                    }
                }',
                '<?php
                class Sample extends ParentSample
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
                class Sample extends ParentSample
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
                class Sample extends ParentSample
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
            'Allows actions that do not involve the instance' => [
                '<?php
                class Sample extends ParentSample
                {
                    public function __construct($token)
                    {
                        $internalToken = $token . "a";
                        parent::__construct($internalToken);
                    }
                }',
                null,
            ],
            'Allows actions with structures that do not involve the instance' => [
                '<?php
                class Sample extends ParentSample
                {
                    public function __construct($token)
                    {
                        if (is_string($token) && trim($token, " \t\n\r\0\x0B") === \'\' && $token !== \'\') {
                            parent::__construct([T_WHITESPACE, $token]);
                        } else {
                            parent::__construct($token);
                        }
                    }
                }',
                null,
            ],
            'Makes invalid code in some rare cases' => [
                '<?php
                class Sample extends ParentSample
                {
                    public function __construct($token)
                    {
                        parent::__construct($internalToken);
                        $internalToken = $this->token() . "a";
                    }
                }',
                '<?php
                class Sample extends ParentSample
                {
                    public function __construct($token)
                    {
                        $internalToken = $this->token() . "a";
                        parent::__construct($internalToken);
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new CallingParentConstructorFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_feature_calling_parent_constructor';
    }
}
