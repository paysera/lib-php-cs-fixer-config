<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\CheckingExplicitlyFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class CheckingExplicitlyFixerTest extends AbstractPayseraFixerTestCase
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
                    public function sampleFunction($arg1, $arg2)
                    {
                        if (isset($this->softLimit) && strlen($line) > $this->softLimit
                            && !$this->isCommentFound($tokens, $lineNumber)
                        ) {
                            $this->addResult(
                                $tokens,
                                $lineNumber,
                                strlen($line),
                                $this->softLimit,
                                \'soft_limit\'
                            );
                        } elseif (isset($this->hardLimit) && strlen($line) > $this->hardLimit
                            && !$this->isCommentFound($tokens, $lineNumber)
                        ) {
                            $this->addResult(
                                $tokens,
                                $lineNumber,
                                strlen($line),
                                $this->hardLimit,
                                \'hard_limit\'
                            );
                        }
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    public function mapToEntity($data)
                    {
                        $this->logger->debug(\'Mapping confirmation session\', array($data));

                        $confirmationSession = new ConfirmationSession();

                        if (isset($data[\'client_id\'])) {
                            $credentials = $this->credentialsRepository->findByIdentifier($data[\'client_id\']);
                            $client = $credentials === null ? null : $credentials->getClient();
                            if ($client !== null) {
                                $confirmationSession->setClient($client);
                            }
                        }

                        if (isset($data[\'redirect_uri\'])) {
                            $confirmationSession->setRedirectUri($data[\'redirect_uri\']);
                        }
                        if (isset($data[\'response_type\'])) {
                            $confirmationSession->setResponseType($data[\'response_type\']);
                        }
                        if (isset($data[\'scope\'])) {
                            $confirmationSession->setScope($this->scopeManager->fixScopeList($data[\'scope\']));
                        }
                        if (isset($data[\'state\'])) {
                            $confirmationSession->setState($data[\'state\']);
                        }
                        if (isset($data[\'user\'])) {
                            $confirmationSession->setUserInformation($this->userInformationMapper->mapToEntity($data[\'user\']));
                        }

                        return $confirmationSession;
                    }
                }',
                '<?php
                class Sample
                {
                    public function mapToEntity($data)
                    {
                        $this->logger->debug(\'Mapping confirmation session\', array($data));

                        $confirmationSession = new ConfirmationSession();

                        if (!empty($data[\'client_id\'])) {
                            $credentials = $this->credentialsRepository->findByIdentifier($data[\'client_id\']);
                            $client = $credentials === null ? null : $credentials->getClient();
                            if ($client !== null) {
                                $confirmationSession->setClient($client);
                            }
                        }

                        if (!empty($data[\'redirect_uri\'])) {
                            $confirmationSession->setRedirectUri($data[\'redirect_uri\']);
                        }
                        if (!empty($data[\'response_type\'])) {
                            $confirmationSession->setResponseType($data[\'response_type\']);
                        }
                        if (!empty($data[\'scope\'])) {
                            $confirmationSession->setScope($this->scopeManager->fixScopeList($data[\'scope\']));
                        }
                        if (!empty($data[\'state\'])) {
                            $confirmationSession->setState($data[\'state\']);
                        }
                        if (!empty($data[\'user\'])) {
                            $confirmationSession->setUserInformation($this->userInformationMapper->mapToEntity($data[\'user\']));
                        }

                        return $confirmationSession;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    protected function parseExtensions(ReservationCode $reservationCode)
                    {
                        $extensions = $reservationCode->getExtensionData();
                        while (($extensions) !== \'\') {
                            $extensionId = ord($extensions[0]);
                            $extensions = substr($extensions, 1);
                            if (!isset($this->parserExtensions[$extensionId])) {
                                throw new ParsingException(\'No extension found with such ID: \' . $extensionId);
                            }
                            $extensions = $this->parserExtensions[$extensionId]->apply($reservationCode, $extensions);
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    protected function parseExtensions(ReservationCode $reservationCode)
                    {
                        $extensions = $reservationCode->getExtensionData();
                        while (strlen($extensions) > 0) {
                            $extensionId = ord($extensions[0]);
                            $extensions = substr($extensions, 1);
                            if (empty($this->parserExtensions[$extensionId])) {
                                throw new ParsingException(\'No extension found with such ID: \' . $extensionId);
                            }
                            $extensions = $this->parserExtensions[$extensionId]->apply($reservationCode, $extensions);
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function sampleFunction($arg1, $arg2, $arg3)
                    {
                        if (($arg1) !== \'\') {
                            return $arg1;
                        } elseif (count($arg2) > 0) {
                            return $arg2;
                        } elseif (count($arg3) === 0) {
                            return null;
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function sampleFunction($arg1, $arg2, $arg3)
                    {
                        if (strlen($arg1) > 0) {
                            return $arg1;
                        } elseif (!empty($arg2)) {
                            return $arg2;
                        } elseif (empty($arg3)) {
                            return null;
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
            new CheckingExplicitlyFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_checking_explicitly';
    }
}
