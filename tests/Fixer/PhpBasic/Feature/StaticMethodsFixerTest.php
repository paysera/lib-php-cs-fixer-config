<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\StaticMethodsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class StaticMethodsFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php class Sample
                {
                    public static function getStatusList()
                    {
                        return [
                            static::STATUS_NEW => static::STATUS_NEW,
                            self::STATUS_FAILED_TO_INITIATE => self::STATUS_FAILED_TO_INITIATE,
                            static::STATUS_ACTIVE => static::STATUS_ACTIVE,
                            self::STATUS_DONE => self::STATUS_DONE,
                        ];
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    public static function getStatusList()
                    {
                        return self::TYPE_A;
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    public static function getStatusList()
                    {
                        return self::$statusList;
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    public static function someFunction() // TODO: "someFunction" - PhpBasic convention 3.11: Static function must return only "self" or "static" constants
                    {
                        return SomeClass::get("something");
                    }
                }',
                '<?php class Sample
                {
                    public static function someFunction()
                    {
                        return SomeClass::get("something");
                    }
                }'
            ],
            [
                '<?php class Sample
                {
                    public static function create() // TODO: "create" - PhpBasic convention 3.11: Static function must return only "self" or "static" constants
                    {
                        return new static();
                    }
                }',
                '<?php class Sample
                {
                    public static function create()
                    {
                        return new static();
                    }
                }'
            ],
            [
                '<?php class Sample
                {
                    private static function getMath() // TODO: "getMath" - PhpBasic convention 3.11: Static function must return only "self" or "static" constants
                    {
                        static $instance;
                
                        if ($instance === null) {
                            $instance = new BcMath(self::DEFAULT_SCALE);
                        }
                
                        return $instance;
                    }
                }',
                '<?php class Sample
                {
                    private static function getMath()
                    {
                        static $instance;
                
                        if ($instance === null) {
                            $instance = new BcMath(self::DEFAULT_SCALE);
                        }
                
                        return $instance;
                    }
                }'
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new StaticMethodsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_static_methods';
    }
}
