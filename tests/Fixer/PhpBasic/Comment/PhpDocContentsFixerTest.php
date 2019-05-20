<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Comment;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocContentsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class PhpDocContentsFixerTest extends AbstractPayseraFixerTestCase
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
                    /**
                     * @param $arg1 @TODO: missing parameter typecast
                     * @param $arg2 @TODO: missing parameter typecast
                     * @param $arg3 @TODO: missing parameter typecast
                     * @param array $arg4
                     * @throws \Exception
                     * @TODO: missing return statement
                     */
                    public function __construct($arg1, $arg2, $arg3, array $arg4)
                    {
                        if ($arg1) {
                            throw new \Exception();
                        } else {
                            return $arg2;
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param $arg1
                     * @param $arg2
                     */
                    public function __construct($arg1, $arg2, $arg3, array $arg4)
                    {
                        if ($arg1) {
                            throw new \Exception();
                        } else {
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
                     * @param ValueClass|null $value
                     */
                    public function sampleFunction(ValueClass $value = null)
                    {
                 
                    }
                }',
                '<?php
                class Sample
                {                    
                    /**
                     * @param $value
                     * @return null
                     */
                    public function sampleFunction(ValueClass $value = null)
                    {
                 
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {                    
                    /**
                     * @param ValueClass|null $value
                     */
                    public function sampleFunction(ValueClass $value = null)
                    {
                 
                    }
                }',
                '<?php
                class Sample
                {                    
                    /**
                     * @param ValueClass $value
                     * @return null
                     */
                    public function sampleFunction(ValueClass $value = null)
                    {
                 
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Set macId
                     *
                     * @param string @TODO: missing parameter variable
                     *
                     * @return $this
                     *
                     * @throws \InvalidArgumentException
                     */
                    public function setMacId($macId)
                    {
                        if (!is_scalar($macId)) {
                            throw new \InvalidArgumentException(\'Mac id must be string\');
                        }
                        $this->macId = (string) $macId;
                        return $this;
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * Set macId
                     *
                     * @param string
                     *
                     * @return $this
                     *
                     * @throws \InvalidArgumentException
                     */
                    public function setMacId($macId)
                    {
                        if (!is_scalar($macId)) {
                            throw new \InvalidArgumentException(\'Mac id must be string\');
                        }
                        $this->macId = (string) $macId;
                        return $this;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param Money $amount
                     * @param string $toCurrency
                     * @TODO: missing return statement
                     * @throws CurrencyNotFoundException
                     * @throws CurrencyPricingException
                     * @throws CurrencyConverterException
                     */
                    public function convert(Money $amount, $toCurrency)
                    {
                        if ($amount->getCurrency() === $toCurrency) {
                            return $amount;
                        } elseif ($amount->isZero()) {
                            return Money::createZero($toCurrency);
                        }
                
                        try {
                            try {
                                $result = $this->client->calculateResult($amount, $toCurrency);
                                return new Money($result[\'amount\'], $result[\'currency\']);
                            } catch (CurrencyPricingException $exception) {
                                if ($exception->getCode() == 404) {
                                    throw new CurrencyNotFoundException(\'Currency not found: \' . $toCurrency, null, $exception);
                                } else {
                                    throw $exception;
                                }
                            }
                        } catch (CurrencyPricingException $exception) {
                            throw new CurrencyConverterException($exception->getMessage(), null, $exception);
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param Money $amount
                     * @param string $toCurrency
                     */
                    public function convert(Money $amount, $toCurrency)
                    {
                        if ($amount->getCurrency() === $toCurrency) {
                            return $amount;
                        } elseif ($amount->isZero()) {
                            return Money::createZero($toCurrency);
                        }
                
                        try {
                            try {
                                $result = $this->client->calculateResult($amount, $toCurrency);
                                return new Money($result[\'amount\'], $result[\'currency\']);
                            } catch (CurrencyPricingException $exception) {
                                if ($exception->getCode() == 404) {
                                    throw new CurrencyNotFoundException(\'Currency not found: \' . $toCurrency, null, $exception);
                                } else {
                                    throw $exception;
                                }
                            }
                        } catch (CurrencyPricingException $exception) {
                            throw new CurrencyConverterException($exception->getMessage(), null, $exception);
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param int $offset
                     * @return \Doctrine\DBAL\Driver\Statement
                     */
                    private function getStatement($offset)
                    {
                        $query = \'SELECT id, value FROM user_contact WHERE type = "%s" AND id > %d ORDER BY id LIMIT 100\';
                        $sql = sprintf($query, UserContact::TYPE_PHONE, $offset);
                
                        return $this->connection->query($sql);
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param array $spotIdArray
                     * @throws \Exception
                     * @throws CurlException
                     * @throws BadResponseException
                     */
                    protected function processPendingSpot(array $spotIdArray)
                    {
                        try {
                            $info = $this->worapayManager->info($userId);
                
                            //check if spot is still checked-in in WoraPay:
                            $checkInExists = false;
                            $remoteBillIds = array(); //remotely existing bill ids
                            $posType = null;
                            $posName = null;
                
                            $this->logger->info(\'Received user info from worapay\', array($info));
                
                        } catch (CurlException $curlException) {
                            $connection->rollBack();
                            $this->logger->debug(\'CurlException details\', array(
                                \'exception\' => $curlException->getMessage(),
                            ));
                            throw new CurlException(\'Got CurlException when processing Spot\');
                        } catch (BadResponseException $badResponse) {
                            $connection->rollBack();
                            $this->logger->debug(\'BadResponseException details\', [
                                \'exception\' => $badResponse,
                            ]);
                            throw new BadResponseException(\'Got BadResponseException when processing Spot\');
                        } catch (\Exception $exception) {
                            $connection->rollBack();
                            throw $exception;
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param array $spotIdArray
                     * @throws \Exception
                     */
                    protected function processPendingSpot(array $spotIdArray)
                    {
                        try {
                            $info = $this->worapayManager->info($userId);
                
                            //check if spot is still checked-in in WoraPay:
                            $checkInExists = false;
                            $remoteBillIds = array(); //remotely existing bill ids
                            $posType = null;
                            $posName = null;
                
                            $this->logger->info(\'Received user info from worapay\', array($info));
                
                        } catch (CurlException $curlException) {
                            $connection->rollBack();
                            $this->logger->debug(\'CurlException details\', array(
                                \'exception\' => $curlException->getMessage(),
                            ));
                            throw new CurlException(\'Got CurlException when processing Spot\');
                        } catch (BadResponseException $badResponse) {
                            $connection->rollBack();
                            $this->logger->debug(\'BadResponseException details\', [
                                \'exception\' => $badResponse,
                            ]);
                            throw new BadResponseException(\'Got BadResponseException when processing Spot\');
                        } catch (\Exception $exception) {
                            $connection->rollBack();
                            throw $exception;
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param InputInterface $input
                     * @param OutputInterface $output
                     * @throws \RuntimeException
                     */
                    protected function execute(InputInterface $input, OutputInterface $output)
                    {
                        $locationId = $input->getArgument(\'location_id\');
                        $prefix = $input->getOption(\'prefix\');
                        /** @var LocationCodeConverter $converter */
                        $converter = $this->getContainer()->get(\'web_to_pay_pos.location_code_converter\');
                
                        $encodedLocationId = $converter->getLocationCodeFromLocationId($locationId);
                        if (strlen($encodedLocationId) !== 4) {
                            throw new \RuntimeException(\'Could not encode location_id, integer is too big\');
                        }
                        $output->writeln($prefix . $encodedLocationId);
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param InputInterface $input
                     * @param OutputInterface $output
                     * @return int|null|void
                     * @throws \RuntimeException
                     */
                    protected function execute(InputInterface $input, OutputInterface $output)
                    {
                        $locationId = $input->getArgument(\'location_id\');
                        $prefix = $input->getOption(\'prefix\');
                        /** @var LocationCodeConverter $converter */
                        $converter = $this->getContainer()->get(\'web_to_pay_pos.location_code_converter\');
                
                        $encodedLocationId = $converter->getLocationCodeFromLocationId($locationId);
                        if (strlen($encodedLocationId) !== 4) {
                            throw new \RuntimeException(\'Could not encode location_id, integer is too big\');
                        }
                        $output->writeln($prefix . $encodedLocationId);
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param string $refreshTokenValue
                     * @param string|null $scope
                     * @param string|null $code
                     *
                     * @throws \Paysera\Bundle\RestBundle\Exception\ApiException
                     * @throws \Evp_RestApi_Exception_RestClientException|\Exception
                     */
                    protected function createRefreshedToken($refreshTokenValue, $scope = null, $code = null)
                    {
                        if ($refreshTokenValue === null) {
                            throw new ApiException(ApiException::INVALID_REQUEST);
                        }
                
                        $client = $this->permissionManager->getCurrentClient();
                
                        $this->autoCommit->begin();
                        /** @var $refreshToken RefreshToken */
                        $refreshToken = $this->refreshTokenRepository->findByToken($refreshTokenValue, true);
                
                        if ($refreshToken === null || $refreshToken->getClient() !== $client) {
                            throw new ApiException(ApiException::INVALID_GRANT, \'No such refresh token\');
                        } elseif (!$refreshToken->isStatusValid()) {
                            throw new ApiException(ApiException::INVALID_GRANT, \'Refresh token expired\');
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param string $refreshTokenValue
                     * @param string $scope
                     * @param string $code
                     *
                     * @throws \Paysera\Bundle\RestBundle\Exception\ApiException
                     * @throws \Evp_RestApi_Exception_RestClientException|\Exception
                     */
                    protected function createRefreshedToken($refreshTokenValue, $scope = null, $code = null)
                    {
                        if ($refreshTokenValue === null) {
                            throw new ApiException(ApiException::INVALID_REQUEST);
                        }
                
                        $client = $this->permissionManager->getCurrentClient();
                
                        $this->autoCommit->begin();
                        /** @var $refreshToken RefreshToken */
                        $refreshToken = $this->refreshTokenRepository->findByToken($refreshTokenValue, true);
                
                        if ($refreshToken === null || $refreshToken->getClient() !== $client) {
                            throw new ApiException(ApiException::INVALID_GRANT, \'No such refresh token\');
                        } elseif (!$refreshToken->isStatusValid()) {
                            throw new ApiException(ApiException::INVALID_GRANT, \'Refresh token expired\');
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Deletes AccessToken
                     *
                     * @throws \Paysera\Bundle\RestBundle\Exception\ApiException
                     * @TODO: missing return statement
                     */
                    public function deleteToken()
                    {
                        $accessToken = $this->permissionManager->getCurrentAccessToken();
                        if (!$accessToken) {
                            throw new ApiException(ApiException::INVALID_PARAMETERS, \'access_token must be provided\');
                        }
                
                        $accessToken->markStatusAsDeleted();
                
                        // Also delete the refresh token
                        $accessToken->getRelatedRefreshToken()->setStatus(RefreshToken::STATUS_DELETED);
                
                        $this->entityManager->flush();
                
                        return $accessToken;
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * Deletes AccessToken
                     *
                     * @throws \Paysera\Bundle\RestBundle\Exception\ApiException
                     */
                    public function deleteToken()
                    {
                        $accessToken = $this->permissionManager->getCurrentAccessToken();
                        if (!$accessToken) {
                            throw new ApiException(ApiException::INVALID_PARAMETERS, \'access_token must be provided\');
                        }
                
                        $accessToken->markStatusAsDeleted();
                
                        // Also delete the refresh token
                        $accessToken->getRelatedRefreshToken()->setStatus(RefreshToken::STATUS_DELETED);
                
                        $this->entityManager->flush();
                
                        return $accessToken;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * Check access validation request
                     *
                     * @param int $requestId
                     *
                     * @throws \Paysera\Bundle\RestBundle\Exception\ApiException
                     * @throws AccessDeniedException
                     */
                    protected function checkAccessValidationRequest($requestId)
                    {
                        $identificationRequestData = $this->identificationRequestRepository->getOneOrNullByRequestId($requestId);
                
                        if ($identificationRequestData !== null && $this->clientAndProjectMatches($identificationRequestData)) {
                            return;
                        }
                
                        if (!$this->permissionManager->canMakeValidIdentificationRequests()) {
                            throw new AccessDeniedException();
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * Check access validation request
                     *
                     * @param int $requestId
                     *
                     * @throws \Paysera\Bundle\RestBundle\Exception\ApiException
                     */
                    protected function checkAccessValidationRequest($requestId)
                    {
                        $identificationRequestData = $this->identificationRequestRepository->getOneOrNullByRequestId($requestId);
                
                        if ($identificationRequestData !== null && $this->clientAndProjectMatches($identificationRequestData)) {
                            return;
                        }
                
                        if (!$this->permissionManager->canMakeValidIdentificationRequests()) {
                            throw new AccessDeniedException();
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
            new PhpDocContentsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_comment_php_doc_contents';
    }
}
