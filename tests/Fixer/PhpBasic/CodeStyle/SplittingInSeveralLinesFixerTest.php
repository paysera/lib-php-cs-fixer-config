<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\SplittingInSeveralLinesFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class SplittingInSeveralLinesFixerTest extends AbstractFixerTestCase
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
                    public function shouldPostFieldsBeSigned($request)
                    {
                        if (!$this->config->get(\'disable_post_params\')
                            && $request instanceof EntityEnclosingRequestInterface
                            && false !== strpos($request->getHeader(\'Content-Type\'), \'application/x-www-form-urlencoded\'))
                        {
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
                            false !== strpos($request->getHeader(\'Content-Type\'), \'application/x-www-form-urlencoded\'))
                        {
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
            [
                '<?php class Sample {
                    private function fixIsNullFunction(Tokens $tokens, $startClearIndex, $endClearIndex, $identical)
                    {
                        $endParenthesesIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $endClearIndex + 1);
                        $tokens->insertAt(++$endParenthesesIndex, new Token([T_WHITESPACE, \' \']));
                        $tokens->insertAt(++$endParenthesesIndex, new Token(
                            $identical ? [T_IS_IDENTICAL, \'===\'] : [T_IS_NOT_IDENTICAL, \'!==\']
                        ));
                        $tokens->insertAt(++$endParenthesesIndex, new Token([T_WHITESPACE, \' \']));
                        $tokens->insertAt(++$endParenthesesIndex, new Token([T_STRING, \'null\']));
                
                        $tokens->clearRange($startClearIndex, $endClearIndex);
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
            [
                '<?php $emailRule = array_filter(
                    $this->emailRules,
                    function($rule) use ($identityDocument, $facePhotoDocument) {
                        return $rule["document"] === $identityDocument->getReviewStatus()
                            && (
                                ($rule["face_photo"] === "" && $facePhotoDocument === null)
                                || ($facePhotoDocument !== null && $rule["face_photo"] === $facePhotoDocument->getReviewStatus())
                            );
                    }
                );',
                null,
            ],
            [
                '<?php return array_map(
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
                        $this->generateUrl(
                            $this->routeContact,
                            [
                                "userId" => $contactData["user_id"]["something"],
                                "contactId" => $contactData["contact_id"],
                            ]
                        );
                    }
                }',
                '<?php class Sample {
                    public function findAllValidByUserId($userId)
                    {
                        $this->generateUrl($this->routeContact, [
                            "userId" => $contactData["user_id"]["something"],
                            "contactId" => $contactData["contact_id"],
                        ]);
                    }
                }'
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
            [
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
            [
                '<?php class Sample {
                    private function sampleFunction(){
                        $a = 0;

                        $this->sampleFunction(
                            $a,
                            1,
                            2,
                            3,
                            [
                                "some",
                                "thing",
                            ]
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

                        in_array(
                            $a,
                            [
                                1,
                                2,
                                3,
                                4,
                                5
                            ],
                            true
                        );
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
                            return ($a
                                && $b
                                || $c
                                && $d
                            );
                        }

                        if ($a === 2) {
                            return ($a
                                && $b
                                || $c
                                && $d
                            );
                        }

                        if ($a === 3) {
                            return ((
                                $a
                                && $b
                            )
                                || (
                                $c
                                && $d
                            ));
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
