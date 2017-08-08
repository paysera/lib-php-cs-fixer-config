<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ComparisonOrderFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class ComparisonOrderFixerTest extends AbstractFixerTestCase
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
                    public function validate($object, Constraint $constraint)
                    {
                        if ($object->getDebitCommission() === null && $object->getCreditCommission() === null) {
                            return;
                        }
                    
                        if ($object->getDebitCommission() !== null && $object->getPayment()->getBeneficiaryIdentifier() === null) {
                            $this->context->addViolation($constraint->message);
                        }
                    
                        $paymentAmountInCents = $object->getPayment()->getPrice()->getAmountInCents();
                        if ($object->getDebitCommission() !== null) {
                            $paymentAmountInCents -= $object->getDebitCommission()->getAmountInCents();
                        }
                        if ($object->getCreditCommission() !== null) {
                            $paymentAmountInCents -= $object->getCreditCommission()->getAmountInCents();
                        }
                    
                        if ($paymentAmountInCents <= 0) {
                            $this->context->addViolation($constraint->message);
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function validate($object, Constraint $constraint)
                    {
                        if (null === $object->getDebitCommission() && null === $object->getCreditCommission()) {
                            return;
                        }
                    
                        if (null !== $object->getDebitCommission() && null === $object->getPayment()->getBeneficiaryIdentifier()) {
                            $this->context->addViolation($constraint->message);
                        }
                    
                        $paymentAmountInCents = $object->getPayment()->getPrice()->getAmountInCents();
                        if (null !== $object->getDebitCommission()) {
                            $paymentAmountInCents -= $object->getDebitCommission()->getAmountInCents();
                        }
                        if (null !== $object->getCreditCommission()) {
                            $paymentAmountInCents -= $object->getCreditCommission()->getAmountInCents();
                        }
                    
                        if ($paymentAmountInCents <= 0) {
                            $this->context->addViolation($constraint->message);
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function onPaymentDone(PaymentEvent $event)
                    {
                        $payment = $event->getPayment();
                    
                        if ($payment->getCommission() !== null
                            && (
                                $payment->getCommission()->getCreditCommission() !== null
                                || $payment->getCommission()->getDebitCommission() !== null
                            )
                        ) {
                            $paidCommission = new PaidCommission();
                            $paidCommission->setWallet($payment->getProject()->getWallet());
                            $paidCommission->setCommissionEntity($payment->getCommission());
                            $paidCommission->setStatus(PaidCommission::STATUS_PENDING);
                    
                            $this->entityManager->persist($paidCommission);
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function onPaymentDone(PaymentEvent $event)
                    {
                        $payment = $event->getPayment();
                    
                        if (null !== $payment->getCommission()
                            && (
                                null !== $payment->getCommission()->getCreditCommission()
                                || null !== $payment->getCommission()->getDebitCommission()
                            )
                        ) {
                            $paidCommission = new PaidCommission();
                            $paidCommission->setWallet($payment->getProject()->getWallet());
                            $paidCommission->setCommissionEntity($payment->getCommission());
                            $paidCommission->setStatus(PaidCommission::STATUS_PENDING);
                    
                            $this->entityManager->persist($paidCommission);
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function shouldPostFieldsBeSigned($request)
                    {
                        if (!$this->config->get(\'disable_post_params\') &&
                            $request instanceof EntityEnclosingRequestInterface &&
                            strpos($request->getHeader(\'Content-Type\'), \'application/x-www-form-urlencoded\') !== false)
                        {
                            return true;
                        }
                
                        return false;
                    }
                }',
                '<?php
                class Sample
                {
                    public function shouldPostFieldsBeSigned($request)
                    {
                        if (!$this->config->get(\'disable_post_params\') &&
                            $request instanceof EntityEnclosingRequestInterface &&
                            false !== strpos($request->getHeader(\'Content-Type\'), \'application/x-www-form-urlencoded\'))
                        {
                            return true;
                        }
                
                        return false;
                    }
                }',
            ],
            [
                '<?php return $value - 1 === $someValue || $this->request->getScheme() . \'://\' === $this->defaultScheme;',
                null,
            ],
            [
                '<?php return $anotherValue . "some string" !== $someOtherValue || $value + 1 === $someValue;',
                null,
            ],
            [
                '<?php
                    class Sample
                    {
                        private function someOtherFunction()
                        {
                            return true;
                        }
                    
                        public function sampleFunction()
                        {
                            $a = 0;
                            $b = "string";
                            $d = false;
                            $f = null;
                            
                            if ($a == 0) {
                                if ($b === "string") {
                                    if ($this->someOtherFunction() !== true) {
                                        if ($d != false) {
                                            if ($f !== null) {
                                                return;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                ',
                '<?php
                    class Sample
                    {
                        private function someOtherFunction()
                        {
                            return true;
                        }
                    
                        public function sampleFunction()
                        {
                            $a = 0;
                            $b = "string";
                            $d = false;
                            $f = null;
                            
                            if (0 == $a) {
                                if ("string" === $b) {
                                    if (true !== $this->someOtherFunction()) {
                                        if (false != $d) {
                                            if (null !== $f) {
                                                return;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                ',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ComparisonOrderFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_comparison_order';
    }
}
