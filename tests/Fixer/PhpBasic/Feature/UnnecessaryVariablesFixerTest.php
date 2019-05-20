<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\UnnecessaryVariablesFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class UnnecessaryVariablesFixerTest extends AbstractPayseraFixerTestCase
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
                    public function getSomething()
                    {
                        return get();
                    }
                }',
                '<?php
                class Sample
                {
                    public function getSomething()
                    {
                        $a = get();
                        return $a;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function getSomething()
                    {
                        if (something()) {
                            doSomething();
                            return get();
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function getSomething()
                    {
                        if (something()) {
                            doSomething();
                            $a = get();
                            return $a;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function getSomething()
                    {
                        return get();
                    }
                }',
                '<?php
                class Sample
                {
                    public function getSomething()
                    {
                        $a = get(); // this calls get() and sets it to variable a
                        
                        return /* returns a */ $a;
                    }
                }',
            ],
            'fixes several occurrences in file' => [
                '<?php
                function getSomething()
                {
                    return get();
                }
                function make() {
                    return get(12, "we" . (123 * 33));
                }',
                '<?php
                function getSomething()
                {
                    $a = get();
                    return $a;
                }
                function make() {
                    $b = get(12, "we" . (123 * 33));
                    return $b;
                }',
            ],
            'does not inline when it can be used for readability' => [
                '<?php
                class Sample
                {
                    public function getSomething()
                    {
                        $currentBalanceForUser = getFromDb(123, "balance");
                        return sendEmailAbout($currentBalanceForUser);
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    public function generate($length = null)
                    {
                        if ($length === null) {
                            $length = $this->defaultLength;
                        }
                        return $this->secureRandom->nextBytes($length);
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    public function deactivateWallet($walletId)
                    {
                        $wallet = $this->deactivateWalletNoFlush($walletId);
                        $this->entityManager->flush();
                        return $wallet;
                    }
                }',
                null,
            ],
            'does not change order of called methods' => [
                '<?php
                class Sample
                {
                    public function myMethod()
                    {
                        $variable = makeFirstThing();
                        makeSecondThing();
                        return $variable;
                    }
                }',
                null,
            ],
            'does not inline if in condition' => [
                '<?php
                class Sample
                {
                    private function getSomething()
                    {
                        $c = doSomething();
                        if ($c) {
                            return true;
                        }
                        return false;
                    }
                }',
                null,
            ],
            'does not inline if several methods have to be called' => [
                '<?php
                class Sample
                {
                    private function getSomething()
                    {
                        $a = get();
                        $b = getAll();
                        if (true) {
                            return $a;
                        } else {
                            return $b;
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function getSomething()
                    {
                        $a = get();
                        $b = getAll();
                        $a = $b;
                        $c = $a;
                        if (true) {
                            return $a;
                        } else {
                            return $b;
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $now = new \DateTime();
                        return $now->add(new \DateInterval($intervalSpec));
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 0;
                        $b = $a;
                    }
                }',
                null,
            ],
            'does not inline when variables make constructs - this makes it easier to understand the code' => [
                '<?php
                class Sample
                {
                    protected function supports($attribute, $subject)
                    {
                        $hasScope = isset($this->permissionScopeMap[$attribute]);
                        $supports = $subject === null || $subject instanceof Restriction || $subject instanceof RestrictionFilter;
                        
                        return $hasScope && $supports;
                    }
                }',
                null,
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new UnnecessaryVariablesFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_unnecessary_variables';
    }
}
