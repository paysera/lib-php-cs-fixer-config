<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ReturnAndArgumentTypesFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class ReturnAndArgumentTypesFixerTest extends AbstractFixerTestCase
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
                namespace WebToPay\ApiBundle\Repository;
                class UserAvatarMapper implements NormalizerInterface
                {
                    /**
                     * Finds active allowance for project and wallet
                     *
                     * @param Location       $location
                     * @param Client|integer $client
                     * @param integer|Wallet $wallet
                     * @param bool           $forceClient
                     *
                     * @return \WebToPay\ApiBundle\Entity\Allowance|null
                     */
                    public function findActiveForWallet(Location $location, $client, $wallet, $forceClient)
                    {
                        $allowance = $this->findActive($location->getProject(), $client, $wallet, $forceClient);
                        return $allowance !== null && $this->isLocationValid($allowance, $location) ? $allowance : null;
                    }
                
                    /**
                     * Cancels all allowances that are currently active for this project and wallet
                     *
                     * @param Project|int $project
                     * @param Client|int  $client
                     * @param Wallet|int  $wallet
                     */
                    public function cancelActiveAllowances($project, $client, $wallet)
                    {
                        $allowance = $this->findActive($project, $client, $wallet);
                        if ($allowance !== null) {
                            $allowance->markAsInactive();
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class UserAvatarMapper implements NormalizerInterface
                {
                    /**
                     * @param UserAvatar $entity
                     * @return mixed|void @TODO: return only void or type with null
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
                class Sample
                {
                    /**
                     * @param int|string $arg1 @TODO: we do not use multiple types
                     * @param string|bool $arg2 @TODO: we do not use multiple types
                     * @return string|int @TODO: we do not use multiple types
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
                     * @return SomeEntity|bool @TODO: we do not use multiple types
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
                     * @param SomeEntity|bool $arg1 @TODO: we do not use multiple types
                     * @param SomeSameEntity $arg2
                     * @return SomeEntity|SomeSameEntity|null @TODO: use single interface or common class instead
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
                            return "string"; // TODO: "string" - PhpBasic convention 3.17.1: We always return value of one type
                        } else {
                            return 1; // TODO: 1 - PhpBasic convention 3.17.1: We always return value of one type
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
                    /**
                     * @param SomeEntity|AnotherEntity|null $arg1 @TODO: use single interface or common class instead
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
                     * @param SomeEntity|bool $arg1 @TODO: we do not use multiple types
                     * @param SomeSameEntity $arg2
                     * @return SomeEntity|SomeSameEntity|null @TODO: use single interface or common class instead
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
                    /**
                     * Get payments
                     *
                     * @return PaymentInterface[]|Collection @TODO: use single interface or common class instead
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
                     * @return mixed @TODO: we do not use multiple types
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
