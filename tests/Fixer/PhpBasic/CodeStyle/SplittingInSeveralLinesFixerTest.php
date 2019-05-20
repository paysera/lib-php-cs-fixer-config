<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\SplittingInSeveralLinesFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class SplittingInSeveralLinesFixerTest extends AbstractPayseraFixerTestCase
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
            'simple array' => [
                '<?php
                [
                    \'type\' => FundsSource::TYPE_BUY,
                    \'details\' => \'Bought something\',
                ];',
                null,
            ],
            'simple array without prefix and postfix' => [
                '<?php
                [
                    \'type\' => FundsSource::TYPE_BUY,
                    \'details\' => \'Bought something\',
                ];',
                '<?php
                [\'type\' => FundsSource::TYPE_BUY,
                    \'details\' => \'Bought something\',];',
            ],
            'simple array with several levels' => [
                '<?php
                [
                    \'1\' => [
                        \'1.1\' => \'value\',
                        \'1.2\' => \'value\',
                        \'1.3\' => \'value\',
                    ],
                ];',
                null,
            ],
            'ok with several lines' => [
                '<?php class Sample {
                    public function shouldPostFieldsBeSigned()
                    {
                        $mapper->mapToEntity(
                            [
                                \'funds_sources\' => [
                                    \'1111\' => [
                                        \'type\' => FundsSource::TYPE_BUY,
                                        \'details\' => \'Bought something\',
                                    ],
                                    \'2222\' => [
                                        \'type\' => FundsSource::TYPE_SELL,
                                        \'details\' => \'Sold something\',
                                    ],
                                ],
                            ]
                        );
                    }
                }',
                null
            ],
            'Return with fluent interface and several lines when ok' => [
                '<?php
                return $this->getService()
                    ->doStuff()
                ;',
                null
            ],
            'Return with fluent interface and several lines' => [
                '<?php
                return $this->getService()
                    ->doStuff()
                ;',
                '<?php
                return $this-> getService()
                        ->doStuff()
                ;',
            ],
            'Complex multiline logic with parenthesis' => [
                '<?php
                return (
                    (
                        $a
                        && $b
                    )
                    || (
                        $c
                        && $d
                    )
                );',
                '<?php
                return (($a &&
                                $b) ||
                                ($c &&
                                $d)
                            );',
            ],
            'Complex multiline logic without parenthesis' => [
                '<?php
                return (
                        $a
                        && $b
                    ||
                        $c
                        && $d
                );',
                '<?php
                return ($a &&
                                $b ||
                                $c &&
                                $d
                            );',
            ],
            'Complex multiline logic with mixed parenthesis' => [
                '<?php
                return (
                        $a
                        && $b
                    || (
                        $c
                        && $d
                    )
                );',
                '<?php
                return ($a &&
                                $b ||
                                ($c &&
                                 $d)
                            );',
            ],
            [
                '<?php class Sample {
                    public function shouldPostFieldsBeSigned()
                    {
                        return $this->getEntityManager()
                            ->createQuery(
                                \'SELECT t FROM WebToPayApiBundle:TransactionNotification t WHERE
                                t.transaction = :transaction
                                AND t.event = :event\'
                            )
                            ->setParameter(\'transaction\', $transaction)
                            ->setParameter(\'event\', $event)
                            ->getOneOrNullResult()
                        ;
                    }
                }',
                null
            ],
            [
                '<?php class Sample {
                    public function shouldPostFieldsBeSigned($request)
                    {
                        return $this->clientByAccountNumber->getWithCache($accountNumber, function () use ($client, $accountNumber) {
                            return $client->getAccountOwner($accountNumber);
                        });
                    }
                }',
                null
            ],
            'default parameter value' => [
                '<?php class Sample {
                    public function shouldPostFieldsBeSigned($request)
                    {
                        $this->setLogClosure(function ($message, $context = []) use ($logger) {
                            if ($message instanceof \Evp_Soap_Exception) {
                                if ($message->isSenderError()) {
                                    $logger->notice((string)$message, $context);
                                    return;
                                }
                            }
                            $logger->warning((string)$message, $context);
                        });
                    }
                }',
                null
            ],
            [
                '<?php class Sample {
                    public function shouldPostFieldsBeSigned($request)
                    {
                        if (
                            !$this->config->get(\'disable_post_params\')
                            && $request instanceof EntityEnclosingRequestInterface
                            && false !== strpos($request->getHeader(\'Content-Type\'), \'application/x-www-form-urlencoded\')
                        ) {
                            return true;
                        }

                        return false;
                    }
                }',
                '<?php class Sample {
                    public function shouldPostFieldsBeSigned($request)
                    {
                        if (!$this->config->get(\'disable_post_params\') &&
                            $request instanceof EntityEnclosingRequestInterface &&
                            false !== strpos($request->getHeader(\'Content-Type\'), \'application/x-www-form-urlencoded\')) {
                            return true;
                        }

                        return false;
                    }
                }',
            ],
            [
                '<?php class Sample {
                    private function sample()
                    {
                        $parameters = array(
                            \'editAt\' => time() + ($changeAfter ? $changeAfter : 300),    // always close after five minutes
                            \'edit\' => $changeTo ? $this->mapper->encodeTransaction($changeTo) : null,
                            \'confirm\' => $confirm
                        );
                    }
                }',
                null,
            ],
            [
                '<?php class Sample {
                    protected function isPrivacyLevelValid($privacyLevel)
                    {
                        return in_array($privacyLevel, array(
                            EventSubscriber::PRIVACY_LEVEL_LOW,
                            EventSubscriber::PRIVACY_LEVEL_HIGH
                        ), true);
                    }
                }',
                null,
            ],
            'ternary operators in separate line as single argument' => [
                '<?php class Sample {
                    private function something($tokens, $identical)
                    {
                        $tokens->insertAt(0, new Token(
                            $identical ? [T_IS_IDENTICAL, \'===\'] : [T_IS_NOT_IDENTICAL, \'!==\']
                        ));
                    }
                }',
                null,
            ],
            [
                '<?php class Sample {
                    public function findAllCreatedBeforeDate(\DateTime $createdBefore, $limit = 100)
                    {
                        $iterator->uasort(function(Account $first, Account $second) use ($mainAccount) {
                            if ($first->getNumber() === $mainAccount->getNumber()) {
                                return -1;
                            }

                            if ($second->getNumber() === $mainAccount->getNumber()) {
                                return 1;
                            }

                            return 0;
                        });
                    }
                }',
                null,
            ],
            [
                '<?php class Sample {
                    public function findAllCreatedBeforeDate(\DateTime $createdBefore, $limit = 100)
                    {
                        return $this->createQueryBuilder("cd")
                            ->where("cd.createdAt <= :created_before")
                            ->andWhere("cd.status = :status")
                            ->setParameters([
                                "created_before" => $createdBefore,
                                "status" => QuestionnaireDocument::STATUS_NEW,
                            ])
                            ->setMaxResults($limit)
                            ->getQuery()
                            ->getResult()
                        ;
                    }
                }',
                null,
            ],
            'return without colons' => [
                '<?php
                $emailRule = array_filter(
                    $this->emailRules,
                    function($rule) use ($identityDocument, $facePhotoDocument) {
                        return
                            $rule["document"] === $identityDocument->getReviewStatus()
                            && (
                                ($rule["face_photo"] === "" && $facePhotoDocument === null)
                                || ($facePhotoDocument !== null && $rule["face_photo"] === $facePhotoDocument->getReviewStatus())
                            )
                        ;
                    }
                );',
                '<?php
                $emailRule = array_filter(
                    $this->emailRules,
                    function($rule) use ($identityDocument, $facePhotoDocument) {
                        return $rule["document"] === $identityDocument->getReviewStatus()
                            && (
                                ($rule["face_photo"] === "" && $facePhotoDocument === null)
                                || ($facePhotoDocument !== null && $rule["face_photo"] === $facePhotoDocument->getReviewStatus())
                            );
                    }
                );',
            ],
            '2 blocks ending with single new line' => [
                '<?php 
                return array_map(
                    function ($item) {
                        return $item["covenanteeId"];
                    },
                    $this->createQueryBuilderByFilter($filter)
                        ->select("c.covenanteeId")
                        ->setMaxResults($filter->getLimit())
                        ->setFirstResult($filter->getOffset())
                        ->groupBy("c.covenanteeId")
                        ->getQuery()
                        ->getArrayResult()
                );',
                null,
            ],
            [
                '<?php
                $this->logger->alert(
                    "TransferOutTax with status processing exists which cannot be processed automatically",
                    [$transfer]
                );',
                null,
            ],
            [
                '<?php
                $result[] = array(
                    "data" => $this->_data[$key],
                    "position" => $this->getOption($item, "position"),
                    "description" => $this->getOption($item, "description")
                );',
                null,
            ],
            [
                '<?php class Sample {
                    private static $mapExceptions = array(
                        "en" => "en_US"
                    );
                }',
                null,
            ],
            [
                '<?php
                $resolver->setDefaults(array(
                    "data_class" => null,
                    "types" => array(),
                    "type_resolver" => null,
                    "options" => array(),
                    "translation_domain" => "form"
                ));',
                null,
            ],
            [
                '<?php class Sample {
                    public function findAllValidByUserId($userId)
                    {
                        $this->generateUrl($this->routeContact, [
                            "userId" => $contactData["user_id"]["something"],
                            "contactId" => $contactData["contact_id"],
                        ]);
                    }
                }',
                null
            ],
            [
                '<?php class Sample {
                    public function findAllValidByUserId($userId)
                    {
                        return $this->createQueryBuilder("r")
                            ->andWhere("r.userId = :userId")
                            ->andWhere("r.status IN (:statuses)")
                            ->setParameters([
                                "userId" => $userId,
                                "statuses" => [
                                    Restriction::STATUS_ACTIVE,
                                    Restriction::STATUS_PENDING_REMOVAL,
                                ]
                            ])
                            ->getQuery()
                            ->getResult()
                        ;
                    }
                }',
                null,
            ],
            'arrays' => [
                '<?php
                in_array(
                    $a,
                    [
                        1,
                        2,
                        3,
                    ]
                );',
                '<?php
                in_array($a, [1,
                    2, 3,]
                    
                    );'
            ],
            [
                '<?php
                in_array($a, [
                    1,
                    2,
                    3,
                ]);',
                '<?php
                in_array($a, [1,
                    2, 3,]);'
            ],
            [
                '<?php class Sample {
                    public function build(ContainerBuilder $container)
                    {
                        $container->addCompilerPass(new AddTaggedCompilerPass(
                            "paysera_restriction.remote_restriction_manager_registry",
                            "paysera_restriction.remote_restriction_manager",
                            "addRemoteRestrictionManager"
                        ));
                    }
                }',
                null,
            ],
            'double new line on initial line' => [
                '<?php class Sample {
                    private function sampleFunction(){
                        $a = 0;

                        $this->sampleFunction(
                            $a,
                            1,
                            2,
                            3,
                            ["some","thing",]
                        );
                    }
                }',
                '<?php class Sample {
                    private function sampleFunction(){
                        $a = 0;

                        $this->sampleFunction($a,1,
                            2,3,["some","thing",]);
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 0;

                        in_array($a, [
                            1,
                            2,
                            3,
                            4,
                            5
                        ], true);
                    }
                }',
                '<?php class Sample
                {
                    private function sampleFunction()
                    {
                        $a = 0;

                        in_array($a, [1,
                            2, 3, 4,5  ], true);
                    }
                }',
            ],
            [
                '<?php class Sample {
                    private function sampleFunction()
                    {
                        if (in_array($restriction->getStatus(), [
                            Restriction::STATUS_ACTIVE,
                            Restriction::STATUS_PENDING_REMOVAL,
                        ], true)) {
                            $restriction->setStatus(Restriction::STATUS_INACTIVE);
                        }
                    }
                }',
                null,
            ],
            [
                '<?php class Sample {
                    private function sampleFunction(){
                        $a = 0;
                        $b = 1;
                        $c = 2;
                        $d = 3;

                        if ($a === 1) {
                            return (
                                    $a
                                    && $b
                                ||
                                    $c
                                    && $d
                            );
                        }

                        if ($a === 2) {
                            return (
                                    $a
                                    && $b
                                ||
                                    $c
                                    && $d
                            );
                        }

                        if ($a === 3) {
                            return (
                                (
                                    $a
                                    && $b
                                )
                                || (
                                    $c
                                    && $d
                                )
                            );
                        }
                    }
                }',
                '<?php class Sample {
                    private function sampleFunction(){
                        $a = 0;
                        $b = 1;
                        $c = 2;
                        $d = 3;

                        if ($a === 1) {
                            return ($a &&
                                $b ||
                                $c &&
                                $d
                            );
                        }

                        if ($a === 2) {
                            return ($a &&
                                $b
                                ||
                                $c &&
                                $d
                            );
                        }

                        if ($a === 3) {
                            return ((
                                $a
                                &&
                                $b
                            ) ||
                                (
                                $c
                                &&
                                $d
                            ));
                        }
                    }
                }',
            ],
            [
                '<?php class Sample {
                    private function sampleFunction()
                    {
                        $a = 0;

                        if ($a) {
                            in_array(
                                $a,
                                [
                                    1,
                                    2,
                                    3,
                                    4,
                                    5
                                ],
                                $a
                            );
                        }

                        $this->someFunction->dispatch(
                            Sample::SOME_CONSTANT,
                            new SomeClientClass($client, $oldCountryCode)
                        );
                    }
                }',
                null,
            ],
            [
                '<?php class Sample {
                    public function getDefinition()
                    {
                        return new FixerDefinition(
                            \'asd\',
                            [
                                new CodeSample(),
                            ]
                        );
                    }
                }',
                null
            ],
            [
                '<?php class Sample {
                    public function getDefinition()
                    {
                        return new FixerDefinition(
                            \'
                            multi
                            line
                            \',
                            [
                                new CodeSample(),
                            ]
                        );
                    }
                }',
                '<?php class Sample {
                    public function getDefinition()
                    {
                        return new FixerDefinition(\'
                            multi
                            line
                            \',
                            [
                                new CodeSample(),
                            ]
                        );
                    }
                }',
            ],
            [
                '<?php
                return ($a && $b || $c && $d);

                return (
                    $a && $b
                    || $c && $d
                );
                
                return ((
                    $a
                    && $b
                ) || (
                    $c
                    && $d
                ));
                
                return (
                    (
                        $a
                        && $b
                    )
                    || (
                        $c
                        && $d
                    )
                );
                
                return ($a && in_array($b, [1, 2, 3]));
                
                return (
                    $a
                    && in_array($b, [1, 2, 3])
                );
                
                return ($a && in_array(
                    $b,
                    [1, 2, 3]
                ));
                
                return ($a && in_array($b, [
                    1,
                    2,
                    3,
                ]));
                
                return ($a && in_array(
                    $b,
                    [
                        1,
                        2,
                        3,
                    ]
                ));
                
                return (
                    $a
                    && in_array(
                        $b,
                        [
                            1,
                            2,
                            3,
                        ]
                    )
                );
                ',
                null,
            ],
            'with comments' => [
                '<?php
                return [
                    \'a\',    // use 4 spaces, not 8 here
                    \'b\', /* another comment */
                ];',
                '<?php
                return [
                        \'a\',    // use 4 spaces, not 8 here
                        \'b\'  , /* another comment */
                    ];',

            ],
            'with double comments' => [
                '<?php
                return [
                    \'a\',    /* use 4 spaces, not 8 here */
                    \'b\',
                ];',
                '<?php
                return [
                        \'a\',    /* use 4 spaces, not 8 here */
                        \'b\',
                    ];',

            ],
            'with comparison and ternary' => [
                '<?php
                $list->addPrefixWhitespaceItem(
                    count($lastWhiteSpace) > 1 ? new SimpleItemList($lastWhiteSpace) : $lastWhiteSpace[0]
                );',
                null,
            ],
            'with ternary' => [
                '<?php
                $tokenForIndent = $prefixItem !== null
                    ? $prefixItem->firstToken()
                    : $itemList->firstToken()->previousNonWhitespaceToken();',
                null,
            ],
            'with concatenation' => [
                '<?php
                $tokenForIndent = "something"
                    . "something else"
                    . "and something else";',
                null,
            ],
            'with array and comment in beginning' => [
                '<?php
                return [ // comment
                    "key" => "value",
                ];',
                null,
            ],
            'with sub-array and comment in beginning' => [
                '<?php
                return [
                    \'no_unneeded_control_parentheses\' => false, // works too aggressively with large structures
                    \'no_extra_blank_lines\' => [\'tokens\' => [ // don\'t use curly_brace_block to allow splitting elseif blocks
                        \'extra\',
                        \'parenthesis_brace_block\',
                        \'square_brace_block\',
                        \'throw\',
                        \'use\',
                    ]],
                ];',
                null,
            ],
            'allow empty lines in array for grouping' => [
                '<?php
                return [
                    "item1.1" => "value",
                    "item1.2" => "value",
                    
                    "item2.1" => "value",
                    "item2.2" => "value",
                ];',
                null,
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new SplittingInSeveralLinesFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_splitting_in_several_lines';
    }
}
