<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\MagicMethodsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class MagicMethodsFixerTest extends AbstractPayseraFixerTestCase
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
                class Sample
                {
                    private $foo;
                    
                    public function __toString() // TODO: "__toString" - PhpBasic convention 3.12: We do not use __toString method for main functionality
                    {
                        return $this->foo;
                    }
                }',
                '<?php
                class Sample
                {
                    private $foo;
                    
                    public function __toString()
                    {
                        return $this->foo;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function sampleFunction()
                    {
                        return $this->something->__toString(); // TODO: "__toString" - PhpBasic convention 3.12: We do not use __toString method for main functionality
                    }
                }',
                '<?php
                class Sample
                {
                    public function sampleFunction()
                    {
                        return $this->something->__toString();
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function __clone() // TODO: "__clone" - PhpBasic convention 3.14.1: We avoid magic methods
                    {
                        return __call(\'name\'); // TODO: "__call" - PhpBasic convention 3.14.1: We avoid magic methods
                    }
                    
                    public function __call($name, $arguments) // TODO: "__call" - PhpBasic convention 3.14.1: We avoid magic methods
                    {
                        return __clone(); // TODO: "__clone" - PhpBasic convention 3.14.1: We avoid magic methods
                    }
                }',
                '<?php
                class Sample
                {
                    public function __clone()
                    {
                        return __call(\'name\');
                    }
                    
                    public function __call($name, $arguments)
                    {
                        return __clone();
                    }
                }'
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new MagicMethodsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_feature_magic_methods';
    }
}
