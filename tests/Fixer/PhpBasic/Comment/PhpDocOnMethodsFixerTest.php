<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Comment;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocOnMethodsFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class PhpDocOnMethodsFixerTest extends AbstractFixerTestCase
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
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param array $params
                     *
                     * @return \WebToPay\OAuthServerBundle\Entity\AccessToken\RefreshedToken
                     */
                    protected function createAccessTokenHook(array $params)
                    {
                        return $this->createRefreshedToken($params[\'refresh_token\'], $params[\'scope\'], $params[\'code\']);
                    }
                }',
                null,
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * RemoteEvent::data has following keys:
                     *  \'status_id\'
                     *  \'status\'
                     *  \'api_key\'
                     *  \'transfer_id\'
                     *
                     */
                    public function onTransferStatusChanged(RemoteEvent $remoteEvent)
                    {
                        $data = $remoteEvent->getData();
                
                        if (!isset($this->eventMap[$data[\'status\']]) || $data[\'api_key\'] !== $this->apiKey) {
                            $this->logger->debug(\'Got unexpected transfer status from gateway\', $data);
                        } else {
                            $this->entityManager->beginTransaction();
                            $payment = $this->paymentTransferRepository->findPaymentByTransferId($data[\'transfer_id\'], true);
                            if ($payment === null) {
                                $logMessage = \'Payment not found by transfer ID\';
                                if ($data[\'status\'] === \Evp_Api_EvpBankManagement_Transfer::STATUS_RESERVED) {
                                    $this->logger->info($logMessage, $data);
                                } else {
                                    $this->logger->error($logMessage, $data);
                                }
                            } else {
                                $this->dispatcher->dispatch(
                                    $this->eventMap[$data[\'status\']],
                                    new PaymentEvent($payment)
                                );
                                $this->entityManager->flush();
                            }
                            $this->entityManager->commit();
                        }
                        $this->webToPayManagement->deleteTransferStatus($data[\'status_id\']);
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * RemoteEvent::data has following keys:
                     *  \'status_id\'
                     *  \'status\'
                     *  \'api_key\'
                     *  \'transfer_id\'
                     *
                     * @param RemoteEvent $remoteEvent
                     */
                    public function onTransferStatusChanged(RemoteEvent $remoteEvent)
                    {
                        $data = $remoteEvent->getData();
                
                        if (!isset($this->eventMap[$data[\'status\']]) || $data[\'api_key\'] !== $this->apiKey) {
                            $this->logger->debug(\'Got unexpected transfer status from gateway\', $data);
                        } else {
                            $this->entityManager->beginTransaction();
                            $payment = $this->paymentTransferRepository->findPaymentByTransferId($data[\'transfer_id\'], true);
                            if ($payment === null) {
                                $logMessage = \'Payment not found by transfer ID\';
                                if ($data[\'status\'] === \Evp_Api_EvpBankManagement_Transfer::STATUS_RESERVED) {
                                    $this->logger->info($logMessage, $data);
                                } else {
                                    $this->logger->error($logMessage, $data);
                                }
                            } else {
                                $this->dispatcher->dispatch(
                                    $this->eventMap[$data[\'status\']],
                                    new PaymentEvent($payment)
                                );
                                $this->entityManager->flush();
                            }
                            $this->entityManager->commit();
                        }
                        $this->webToPayManagement->deleteTransferStatus($data[\'status_id\']);
                    }
                }',
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    public function another(array $something, int $another)
                    {
                        return $this;
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param array $something
                     * @param int $another
                     */
                    public function another(array $something, int $another)
                    {
                        return $this;
                    }
                }',
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    public function __construct(
                        UniqueKeyGenerator $accessTokenIdentifierGenerator,
                        UniqueKeyGenerator $accessTokenKeyGenerator,
                        UniqueKeyGenerator $refreshTokenKeyGenerator,
                        TokenExpiracyManager $expiracyManager,
                        AccessTokenRepository $accessTokenRepository
                    ) {
                        $this->accessTokenIdentifierGenerator = $accessTokenIdentifierGenerator;
                        $this->accessTokenKeyGenerator = $accessTokenKeyGenerator;
                        $this->refreshTokenKeyGenerator = $refreshTokenKeyGenerator;
                        $this->expiracyManager = $expiracyManager;
                        $this->accessTokenRepository = $accessTokenRepository;
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param UniqueKeyGenerator $accessTokenIdentifierGenerator
                     * @param UniqueKeyGenerator $accessTokenKeyGenerator
                     * @param UniqueKeyGenerator $refreshTokenKeyGenerator
                     * @param TokenExpiracyManager $expiracyManager
                     * @param AccessTokenRepository $accessTokenRepository
                     */
                    public function __construct(
                        UniqueKeyGenerator $accessTokenIdentifierGenerator,
                        UniqueKeyGenerator $accessTokenKeyGenerator,
                        UniqueKeyGenerator $refreshTokenKeyGenerator,
                        TokenExpiracyManager $expiracyManager,
                        AccessTokenRepository $accessTokenRepository
                    ) {
                        $this->accessTokenIdentifierGenerator = $accessTokenIdentifierGenerator;
                        $this->accessTokenKeyGenerator = $accessTokenKeyGenerator;
                        $this->refreshTokenKeyGenerator = $refreshTokenKeyGenerator;
                        $this->expiracyManager = $expiracyManager;
                        $this->accessTokenRepository = $accessTokenRepository;
                    }
                }',
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    public function onPaymentDone(PaymentEvent $event)
                    {
                        $payment = $event->getPayment();
                
                        if ($payment->getCashback() !== null && $payment->getCashback()->isPositive()) {
                            $paymentCashback = new PaymentCashback();
                            $paymentCashback->setPayment($payment);
                            $paymentCashback->setStatus(PaymentCashback::STATUS_NEW);
                            $this->entityManager->persist($paymentCashback);
                        }
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param PaymentEvent $event
                     */
                    public function onPaymentDone(PaymentEvent $event)
                    {
                        $payment = $event->getPayment();
                
                        if ($payment->getCashback() !== null && $payment->getCashback()->isPositive()) {
                            $paymentCashback = new PaymentCashback();
                            $paymentCashback->setPayment($payment);
                            $paymentCashback->setStatus(PaymentCashback::STATUS_NEW);
                            $this->entityManager->persist($paymentCashback);
                        }
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new PhpDocOnMethodsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_comment_php_doc_on_methods';
    }
}
