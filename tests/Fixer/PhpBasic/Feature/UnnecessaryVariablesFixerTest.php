<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\UnnecessaryVariablesFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class UnnecessaryVariablesFixerTest extends AbstractFixerTestCase
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
            [
                '<?php
                class Sample
                {
                    private function getSomething()
                    {
                        $c = getNone();
                        if ($c) {
                            return get();
                        } else {
                            return getAll();
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    private function getSomething()
                    {
                        $a = get();
                        $b = getAll();
                        $c = getNone();
                        if ($c) {
                            return $a;
                        } else {
                            return $b;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private function getSomething()
                    {
                        if (true) {
                            return get();
                        } else {
                            return getAll();
                        }
                    }
                }',
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
            ],
            [
                '<?php
                class Sample
                {
                    public function getStringToSign(RequestInterface $request, $timestamp, $nonce)
                    {
                        // Convert booleans to strings.
                        $params = $this->prepareParameters($params);
                
                        $url = Url::factory($request->getUrl())->setQuery(\'\')->setFragment(null);
                
                        return strtoupper($request->getMethod()) . \'&\'
                             . rawurlencode($url) . \'&\'
                             . rawurlencode((string) new QueryString($params));
                    }
                }',
                '<?php
                class Sample
                {
                    public function getStringToSign(RequestInterface $request, $timestamp, $nonce)
                    {
                        // Convert booleans to strings.
                        $params = $this->prepareParameters($params);
                      
                        $parameterString = new QueryString($params);
                
                        $url = Url::factory($request->getUrl())->setQuery(\'\')->setFragment(null);
                
                        return strtoupper($request->getMethod()) . \'&\'
                             . rawurlencode($url) . \'&\'
                             . rawurlencode((string) $parameterString);
                    }
                }',
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
            [
                '<?php
                class Sample
                {
                    protected function supports($attribute, $subject)
                    {
                        $supports = $subject === null || $subject instanceof Restriction || $subject instanceof RestrictionFilter;
                        
                        return isset($this->permissionScopeMap[$attribute]) && $supports;
                    }
                }',
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
                        $a = get();
                        return $a;
                    }
                }',
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
