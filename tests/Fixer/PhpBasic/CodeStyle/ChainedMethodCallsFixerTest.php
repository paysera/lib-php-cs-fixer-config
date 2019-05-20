<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ChainedMethodCallsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ChainedMethodCallsFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php class Sample {
                    private function sampleFunction()
                    {
                        return Name::create()
                            ->setName(mb_strtoupper($a))
                            ->setSurname(mb_strtoupper($a))
                        ;
                    }
                }',
                '<?php class Sample {
                    private function sampleFunction()
                    {
                        return Name::create()
                            ->setName(mb_strtoupper($a))
                            ->setSurname(mb_strtoupper($a));
                    }
                }'
            ],
            [
                '<?php
                class Sample
                {
                    private function methodA()
                    {
                        $this
                            ->update(\'WebToPayApiBundle:Location\ConcreteLocation\', \'l\')
                            ->set(\'l.status\', $queryBuilder->expr()->literal(Location\ConcreteLocation::STATUS_INACTIVE))
                            ->set(\'l.updatedAt\', $queryBuilder->expr()->literal($date->format(\'Y-m-d H:i:s\')))
                        ;
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function methodA()
                    {
                        $this
                            ->setHoldHours($pricingResult->getHoldHours())
                            ->setSecondaryHold($pricingResult->getSecondaryHold()->ceil())
                            ->setSecondaryHoldHours($pricingResult->getSecondaryHoldHours())
                            ->setPayment($payment)
                        ;
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function audit(string $eventName, Restriction $restriction)
                    {
                        $this->auditor->audit(
                            $this->auditor->createEvent()
                                ->setName($eventName)
                                ->setData([
                                    "restriction_id" => $restriction->getId(),
                                    "user_id" => $restriction->getUserId(),
                                    "restriction_type" => $restriction->getType(),
                                    "administrator_id" => $this->tokenStorage->getToken() !== null
                                        ? $this->tokenStorage->getToken()->getUsername()
                                        : null,
                                    "awaited_condition_type" => $restriction->getAwaitedConditionType(),
                                    "awaited_condition_removal_type" => $restriction->getAwaitedConditionRemovalType(),
                                ])
                        );
                    }
                }',
                null,
            ],
            [
                '<?php
                return $this->auditor->audit(
                    $this->auditor->createEvent()
                        ->setName($eventName)
                        ->setData($data)
                );',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    private function methodA()
                    {
                        return $this->methodB()->methodC()->methodD();
                    }
                    
                    private function methodB()
                    {
                        return $this->methodA()
                            ->methodC()
                            ->methodD()
                        ;
                    }
                    
                    private function methodC()
                    {
                        return $this->methodA()
                            ->methodB()
                            ->methodD()
                        ;
                    }
                    
                    private function methodD()
                    {
                        return $this
                            ->methodA()
                            ->methodB()
                            ->methodC()
                        ;
                    }
                }',
                '<?php
                class Sample
                {
                    private function methodA()
                    {
                        return $this->methodB()->methodC()->methodD();
                    }
                    
                    private function methodB()
                    {
                        return $this->methodA()->methodC()
                            ->methodD();
                    }
                    
                    private function methodC()
                    {
                        return $this->methodA()
                            ->methodB()->methodD();
                    }
                    
                    private function methodD()
                    {
                        return $this
                            ->methodA()
                            ->methodB()
                            ->methodC();
                    }
                }'
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ChainedMethodCallsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_chained_method_calls';
    }
}
