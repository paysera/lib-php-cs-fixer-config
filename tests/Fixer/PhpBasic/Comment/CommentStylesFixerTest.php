<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Comment;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\CommentStylesFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class CommentStylesFixerTest extends AbstractPayseraFixerTestCase
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
                /**
                 * Validates account description uniqueness. Checks client and account description uniqueness
                 * @author FirstName LastName
                 */
                class UniqueAccountDescriptionValidator extends ConstraintValidator
                {
                }',
                '<?php
                /** Validates account description uniqueness. Checks client and account description uniqueness @author FirstName LastName */
                class UniqueAccountDescriptionValidator extends ConstraintValidator
                {
                }',
            ],
            [
                '<?php
                class Sample
                {
                    // some
                    // multi line
                    // comment
                    private function sampleFunction($something)
                    {
                        // Some single liner comment
                        $value = new Sample();
                        // Some comment
                        // Some comment
                    }
                }',
                '<?php
                class Sample
                {
                    /*
                     * some
                     * multi line
                     * comment
                     */
                    private function sampleFunction($something)
                    {
                        /*
                         * Some single liner comment
                         */
                        $value = new Sample();
                        /* Some comment
                         */
                        /*
                        Some comment */
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @param RemoteEvent $event
                     */
                    public function onClientUpdatedRemote(RemoteEvent $event)
                    {
                        /** @var UserInformation $data */
                        $data = $event->getData();
                
                        /** @var Client $client */
                        $client = $this->clientRepository->findOneByCovenanteeId(
                            $data->getGlobalUserId()
                        );
                
                        if (!$client) {
                            return;
                        }
                
                        $this->remoteJobPublisher->publishJob(
                            UpdateClientWorker::JOB_UPDATE_BANKING_HISTORY_CLIENT,
                            function() use ($client) {
                                return [\'id\' => $client->getId()];
                            }
                        );
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param RemoteEvent $event
                     */
                    public function onClientUpdatedRemote(RemoteEvent $event)
                    {
                        /**
                         * @var UserInformation $data
                         */
                        $data = $event->getData();
                
                        /**
                         * @var Client $client
                         */
                        $client = $this->clientRepository->findOneByCovenanteeId(
                            $data->getGlobalUserId()
                        );
                
                        if (!$client) {
                            return;
                        }
                
                        $this->remoteJobPublisher->publishJob(
                            UpdateClientWorker::JOB_UPDATE_BANKING_HISTORY_CLIENT,
                            function() use ($client) {
                                return [\'id\' => $client->getId()];
                            }
                        );
                    }
                }',
            ],
            [
                '<?php
                class NotificationIncomingFundsProcessor
                {
                    /**
                     * @var EntityManager $entityManager
                     */
                    protected $entityManager;
                
                    /**
                     * @var NotificationIncomingFundsDeterminer $determiner
                     */
                    protected $notificationDeterminer;
                
                    /**
                     * @var NotificationIncomingFundsRepository
                     */
                    protected $notificationIncomingFundsRepository;
                }',
                '<?php
                class NotificationIncomingFundsProcessor
                {
                    /** @var EntityManager $entityManager */
                    protected $entityManager;
                
                    /** @var NotificationIncomingFundsDeterminer $determiner */
                    protected $notificationDeterminer;
                
                    /** @var NotificationIncomingFundsRepository */
                    protected $notificationIncomingFundsRepository;
                }',
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @var int $variable
                     */
                    private $variable;
                    
                    /**
                     * @param int $something
                     */
                    private function sampleFunction($something)
                    {
                        /** @var SomeInvalidDocBlock $value */
                        $value = new Sample();
                    }
                }',
                '<?php
                class Sample
                {
                    /** @var int $variable */
                    private $variable;
                    
                    /** @param int $something */
                    private function sampleFunction($something)
                    {
                        /** 
                         * @var SomeInvalidDocBlock $value
                         */
                        $value = new Sample();
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    // Some comment
                    private function sampleFunction()
                    {
                        //Another Comment
                        /** @var SomeValidDocBlock $value */
                        $value = new Sample();
                    }
                }',
                '<?php
                class Sample
                {
                    /* Some comment*/
                    private function sampleFunction()
                    {
                        /*Another Comment*/
                        /** @var SomeValidDocBlock $value */
                        $value = new Sample();
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new CommentStylesFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_comment_comment_styles';
    }
}
