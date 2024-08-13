<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ConditionResultsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class ConditionResultsFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php $a = !($d && $e);',
                '<?php $a = $d && $e ? false : true;',
            ],
            [
                '<?php return !($a && $b);',
                '<?php return $a && $b ? false : true;',
            ],
            [
                '<?php return $d && $e;',
                '<?php return $d && $e ? true : false;',
            ],
            [
                '<?php

                $a = $d && $e;
                return $a && $b;',
                '<?php

                $a = $d && $e ? true : false;
                if ($a && $b) {
                    return true;
                }

                return false;',
            ],
            [
                '<?php
                $a = true;
                $b = true;

                return !($a && $b);',
                '<?php
                $a = true;
                $b = true;

                if ($a && $b) {
                    return false;
                }

                return true;',
            ],
            [
                '<?php
                $a = true;
                $b = true;

                return !($a && $b);',
                '<?php
                $a = true;
                $b = true;

                if ($a && $b) {
                    return false;
                } else {
                    return true;
                }',
            ],
            [
                '<?php
                return !($this->someFunction($a, $b, [1, 2]));',
                '<?php
                if ($this->someFunction($a, $b, [1, 2])) {
                    return false;
                }
                return true;',
            ],
            [
                '<?php
                class Some
                {                
                    public function b($attribute, $user)
                    {
                        return $attribute === "a" && $user instanceof AuthToken;
                    }
                }',
                '<?php
                class Some
                {                
                    public function b($attribute, $user)
                    {
                        if ($attribute === "a" && $user instanceof AuthToken) {
                            return true;
                        }
                        return false;
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ConditionResultsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_feature_condition_results';
    }
}
