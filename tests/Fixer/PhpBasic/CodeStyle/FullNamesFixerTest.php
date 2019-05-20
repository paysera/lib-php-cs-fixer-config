<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\FullNamesFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class FullNamesFixerTest extends AbstractPayseraFixerTestCase
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
                    private $sfr;
                    
                    /**
                     * @param SomeProvider $someProvider
                     * @param SomeFancyRepo $someFancyRepo
                     */
                    public function __construct(
                        SomeProvider $someProvider,
                        SomeFancyRepo $someFancyRepo
                    ) {
                        $this->sfr = $someFancyRepo;
                    }
                }',
                '<?php
                class Sample
                {
                    private $sfr;
                    
                    /**
                     * @param SomeProvider $sp
                     * @param SomeFancyRepo $sfr
                     */
                    public function __construct(
                        SomeProvider $sp,
                        SomeFancyRepo $sfr
                    ) {
                        $this->sfr = $sfr;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction(
                        SomeProvider $someProvider,
                        SomeFancyRepository $someFancyRepository
                    ) {
                        $a = $someProvider;
                        $b = $someFancyRepository;
                    }
                }',
                '<?php
                class Sample
                {
                    private function sampleFunction(
                        SomeProvider $sp,
                        SomeFancyRepository $sfr
                    ) {
                        $a = $sp;
                        $b = $sfr;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function render(Statement $statement, $locale = \'en\')
                    {
                        $covenanteeId = $statement->getCurrencyAccount()->getAccount()->getClient()->getCovenanteeId();
                        try {
                            $userInfo = $this->restApi->getUser($covenanteeId);
                        } catch (\Evp_RestApi_Exception_RestClientException $evpRestApiExceptionRestClientException) {
                            throw new \Exception(sprintf(\'Rest API error, possible user not found by covenenteeId (%s)\', $covenanteeId));
                        }
                    }
                }',
                '<?php
                class Sample
                {
                    public function render(Statement $statement, $locale = \'en\')
                    {
                        $covenanteeId = $statement->getCurrencyAccount()->getAccount()->getClient()->getCovenanteeId();
                        try {
                            $userInfo = $this->restApi->getUser($covenanteeId);
                        } catch (\Evp_RestApi_Exception_RestClientException $e) {
                            throw new \Exception(sprintf(\'Rest API error, possible user not found by covenenteeId (%s)\', $covenanteeId));
                        }
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    public function getWorkflow()
                    {
                        $workflowBuilder = new WorkflowBuilder();
                
                        $workflow = new \ezcWorkflow(\'\');
                
                        $start = new \ezcWorkflowNodeSimpleMerge();
                
                        $workflow->startNode->addOutNode(
                            $start->addOutNode(
                                $workflowBuilder->createInputNode(\'import_result\', $workflowBuilder->_(new \ezcWorkflowNodeExclusiveChoice())
                                    ->addConditionalOutNode(
                                        new \ezcWorkflowConditionVariable(
                                            \'input\',
                                            new IsInstanceOf(
                                                \'Evp\Bundle\TransferProviderBundle\Command\FileImport\Download\'
                                            )
                                        ),
                                        $workflowBuilder->createAction(
                                            new DownloadAction())->addOutNode(
                                                $workflowBuilder->_(new \ezcWorkflowNodeVariableSet(array(\'downloaded\' => true)))->addOutNode($start)
                                            )
                                    )
                                    ->addConditionalOutNode(
                                        new \ezcWorkflowConditionVariable(
                                            \'input\',
                                            new IsInstanceOf(
                                                \'Evp\Bundle\TransferProviderBundle\Command\Accept\'
                                            )
                                        ),
                                        $workflowBuilder->_(new \ezcWorkflowNodeExclusiveChoice())->addConditionalOutNode(
                                            new \ezcWorkflowConditionVariable(\'downloaded\', new \ezcWorkflowConditionIsTrue()),
                                            $workflowBuilder->createAction(new SuccessAction())->addOutNode($workflow->endNode),
                                            // else:
                                            $workflowBuilder->createEmitErrorEventAction(
                                                new WrongStatusError(\'File was not downloaded before accepting\')
                                            )->addOutNode($start)
                                        ),
                                        // else:
                                        $workflowBuilder->createEmitErrorEventAction(new UnsupportedResponseError())->addOutNode($start)
                                    )
                                )
                            )
                        );
                        return $workflow;
                    }
                }',
                '<?php
                class Sample
                {
                    public function getWorkflow()
                    {
                        $wb = new WorkflowBuilder();
                
                        $workflow = new \ezcWorkflow(\'\');
                
                        $start = new \ezcWorkflowNodeSimpleMerge();
                
                        $workflow->startNode->addOutNode(
                            $start->addOutNode(
                                $wb->createInputNode(\'import_result\', $wb->_(new \ezcWorkflowNodeExclusiveChoice())
                                    ->addConditionalOutNode(
                                        new \ezcWorkflowConditionVariable(
                                            \'input\',
                                            new IsInstanceOf(
                                                \'Evp\Bundle\TransferProviderBundle\Command\FileImport\Download\'
                                            )
                                        ),
                                        $wb->createAction(
                                            new DownloadAction())->addOutNode(
                                                $wb->_(new \ezcWorkflowNodeVariableSet(array(\'downloaded\' => true)))->addOutNode($start)
                                            )
                                    )
                                    ->addConditionalOutNode(
                                        new \ezcWorkflowConditionVariable(
                                            \'input\',
                                            new IsInstanceOf(
                                                \'Evp\Bundle\TransferProviderBundle\Command\Accept\'
                                            )
                                        ),
                                        $wb->_(new \ezcWorkflowNodeExclusiveChoice())->addConditionalOutNode(
                                            new \ezcWorkflowConditionVariable(\'downloaded\', new \ezcWorkflowConditionIsTrue()),
                                            $wb->createAction(new SuccessAction())->addOutNode($workflow->endNode),
                                            // else:
                                            $wb->createEmitErrorEventAction(
                                                new WrongStatusError(\'File was not downloaded before accepting\')
                                            )->addOutNode($start)
                                        ),
                                        // else:
                                        $wb->createEmitErrorEventAction(new UnsupportedResponseError())->addOutNode($start)
                                    )
                                )
                            )
                        );
                        return $workflow;
                    }
                }',
            ],
            [
                '<?php
                
                namespace Paysera\BlacklistBundle\Entity;
                
                use Paysera\Component\Serializer\Entity\Filter;
                
                class RelationFilter extends Filter
                {
                    const ORDER_BY_ID = \'id\';
                
                    private $ids;
                
                    public function __construct()
                    {
                        $this->ids = [];
                
                        $this->setOrderBy(self::ORDER_BY_ID);
                        $this->setOrderAsc(true);
                        $this->setOffset(null);
                    }
                
                    public function getIds(): array
                    {
                        return $this->ids;
                    }
                
                    public function setIds(array $ids)
                    {
                        foreach ($ids as $id) {
                            $this->addId($id);
                        }
                        return $this;
                    }
                
                    public function addId(string $id)
                    {
                        if (!in_array($id, $this->ids, true)) {
                            $this->ids[] = $id;
                        }
                
                        return $this;
                    }
                }',
                null,

            ]
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new FullNamesFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_full_names';
    }
}
