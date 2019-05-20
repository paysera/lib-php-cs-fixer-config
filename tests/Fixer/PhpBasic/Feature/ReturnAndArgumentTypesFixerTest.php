<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ReturnAndArgumentTypesFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ReturnAndArgumentTypesFixerTest extends AbstractPayseraFixerTestCase
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
                namespace My\Data;
                class MyClass
                {
                    /**
                     * @param SomeClass[]|Generator $something
                     * @return SomeClass[]|\Generator
                     */
                    private function doSomething($something)
                    {
                    
                    }
                }',
                null,
            ],
            [
                '<?php
                namespace My\Data;
                class MyClass
                {
                    /**
                     * @param SomeClass[]|array $something
                     * @return SomeClass[]|array
                     */
                    private function doSomething($something)
                    {
                    
                    }
                }',
                null,
            ],
            [
                '<?php
                namespace WebToPay\ApiBundle\Repository;
                class UserAvatarMapper implements NormalizerInterface
                {
                    /**
                     * @param SomeClass[]|ArrayCollection $something
                     * @return SomeClass[]|\Doctrine\Collection\ArrayCollection
                     */
                    private function doSomething($something)
                    {
                    
                    }
                }',
                null,
            ],
            [
                '<?php
                namespace MyNamespace\Repository;
                class MyRepository
                {
                    /**
                     * @param Location|null  $location
                     * @param Client|string  $client TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     * @param integer|Wallet $wallet
                     * @param int|Wallet     $otherWallet
                     * @param bool           $forceClient
                     *
                     * @return \WebToPay\ApiBundle\Entity\Allowance|null
                     */
                    public function method1(Location $location, $client, $wallet, $otherWallet, $forceClient)
                    {
                    
                    }
                
                    /**
                     * @param string|boolean $flag TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     *
                     * @return Object|AnotherObject|boolean TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     */
                    public function method2($flag)
                    {
                    
                    }
                }',
                '<?php
                namespace MyNamespace\Repository;
                class MyRepository
                {
                    /**
                     * @param Location|null  $location
                     * @param Client|string  $client
                     * @param integer|Wallet $wallet
                     * @param int|Wallet     $otherWallet
                     * @param bool           $forceClient
                     *
                     * @return \WebToPay\ApiBundle\Entity\Allowance|null
                     */
                    public function method1(Location $location, $client, $wallet, $otherWallet, $forceClient)
                    {
                    
                    }
                
                    /**
                     * @param string|boolean $flag
                     *
                     * @return Object|AnotherObject|boolean
                     */
                    public function method2($flag)
                    {
                    
                    }
                }',
            ],
            [
                '<?php
                class UserAvatarMapper implements NormalizerInterface
                {
                    /**
                     * @param UserAvatar $entity
                     * @return mixed|void TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     */
                    public function mapFromEntity($entity)
                    {
                        return array(\'id\' => $entity->getId());
                    }
                }',
                '<?php
                class UserAvatarMapper implements NormalizerInterface
                {
                    /**
                     * @param UserAvatar $entity
                     * @return mixed|void
                     */
                    public function mapFromEntity($entity)
                    {
                        return array(\'id\' => $entity->getId());
                    }
                }',
            ],
            [
                '<?php
                class UserAvatarMapper implements NormalizerInterface
                {
                    /**
                     * @param UserAvatar $entity
                     * @return mixed|null TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     */
                    public function mapFromEntity($entity)
                    {
                        return array(\'id\' => $entity->getId());
                    }
                }',
                '<?php
                class UserAvatarMapper implements NormalizerInterface
                {
                    /**
                     * @param UserAvatar $entity
                     * @return mixed|null
                     */
                    public function mapFromEntity($entity)
                    {
                        return array(\'id\' => $entity->getId());
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param int|string $arg1 TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     * @param string|bool $arg2 TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     * @return string|int TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     */
                    public function someFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } elseif ($arg2) {
                            return $arg2;
                        }
                    }
                    
                    /**
                     * @param bool $arg1
                     * @param SomeEntity $arg2
                     * @return SomeEntity|bool TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     */
                    public function anotherFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } elseif ($arg2) {
                            return $arg2;
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param int|string $arg1
                     * @param string|bool $arg2
                     * @return string|int
                     */
                    public function someFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } elseif ($arg2) {
                            return $arg2;
                        }
                    }
                    
                    /**
                     * @param bool $arg1
                     * @param SomeEntity $arg2
                     * @return SomeEntity|bool
                     */
                    public function anotherFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } elseif ($arg2) {
                            return $arg2;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param SomeEntity|bool $arg1 TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     * @param SomeSameEntity $arg2
                     * @return SomeEntity|SomeSameEntity|null TODO: use single interface or common class instead (https://bit.ly/psg-return-and-argument-types)
                     */
                    public function someFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } elseif ($arg2) {
                            return null;
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param SomeEntity|bool $arg1
                     * @param SomeSameEntity $arg2
                     * @return SomeEntity|SomeSameEntity|null
                     */
                    public function someFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } elseif ($arg2) {
                            return null;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function someFunction()
                    {
                        if (false) {
                            return true;
                        } elseif (true) {
                            return null;
                        } else {
                            return false;
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    public function someFunction()
                    {
                        if ($arg1) {
                            return true;
                        } elseif ($arg2) {
                            return "string"; // TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                        } else {
                            return 1; // TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function someFunction()
                    {
                        if ($arg1) {
                            return true;
                        } elseif ($arg2) {
                            return "string";
                        } else {
                            return 1;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function someFunction()
                    {
                        if ($arg1) {
                            return;
                        } elseif ($arg2) {
                            return;
                        } else {
                            return 1; // TODO: we always return something or always nothing (https://bit.ly/psg-return-and-argument-types)
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function someFunction()
                    {
                        if ($arg1) {
                            return;
                        } elseif ($arg2) {
                            return;
                        } else {
                            return 1;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function someFunction()
                    {
                        if ($arg1) {
                            return 1;
                        } elseif ($arg2) {
                            return; // TODO: we always return something or always nothing (https://bit.ly/psg-return-and-argument-types)
                        } else {
                            return; // TODO: we always return something or always nothing (https://bit.ly/psg-return-and-argument-types)
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function someFunction()
                    {
                        if ($arg1) {
                            return 1;
                        } elseif ($arg2) {
                            return;
                        } else {
                            return;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param SomeEntity|AnotherEntity|null $arg1 TODO: use single interface or common class instead (https://bit.ly/psg-return-and-argument-types)
                     * @param SomeEntity|null $arg2
                     * @return SomeEntity|null
                     */
                    public function someFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return null;
                        } elseif ($arg2) {
                            return arg2;
                        }
                    }
                    
                    /**
                     * @param bool $arg1
                     * @param SomeEntity $arg2
                     * @return bool|null
                     */
                    public function someOtherFunction($arg1, $arg2)
                    {
                        if ($arg1) {
                            return $arg1;
                        } elseif ($arg2) {
                            return null;
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Get payments
                     *
                     * @return PaymentInterface[]|Collection
                     */
                    public function getPayments()
                    {
                        return $this->transaction->getPayments()->map(
                            function (PaymentInterface $payment) {
                                return new PublicPayment($payment);
                            });
                    }
                    
                    /**
                     * Gets parameters
                     *
                     * @return mixed TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)
                     */
                    public function getParameters()
                    {
                        return null;
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * Get payments
                     *
                     * @return PaymentInterface[]|Collection
                     */
                    public function getPayments()
                    {
                        return $this->transaction->getPayments()->map(
                            function (PaymentInterface $payment) {
                                return new PublicPayment($payment);
                            });
                    }
                    
                    /**
                     * Gets parameters
                     *
                     * @return mixed
                     */
                    public function getParameters()
                    {
                        return null;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Get tokens
                     *
                     * @return Token[]|Tokens
                     */
                    public function getTokens()
                    {
                        
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Get tokens
                     *
                     * @return Token[]|Result<Token>
                     */
                    public function getTokens()
                    {
                        
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
            new ReturnAndArgumentTypesFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_return_and_argument_types';
    }
}
