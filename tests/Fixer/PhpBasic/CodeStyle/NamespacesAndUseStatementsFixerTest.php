<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\NamespacesAndUseStatementsFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class NamespacesAndUseStatementsFixerTest extends AbstractFixerTestCase
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
namespace MyNamespace;
use IteratorAggregate;
use ArrayAccess;

class A implements ArrayAccess, IteratorAggregate
{

}
',
                '<?php
namespace MyNamespace;

class A implements \ArrayAccess, \IteratorAggregate
{

}
',
            ],
            [
                '<?php
namespace Evp\DebugPenaltyBundle\Service;
use InvalidArgumentException;
use Evp\UserSurveillanceBundle\Scenarios;
class ActivityScenarioSubjectMapper
{
    private $map = [
        Scenarios::SCENARIO_CREDENTIALS_BRUTE_FORCE_VERY_STRONG_LINK => \'[Login Bruteforce][Very Strong]\',
    ];

    /**
     * @param string $activity
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function resolve($activity)
    {
        if (!isset($this->map[$activity])) {
            throw new InvalidArgumentException(sprintf(\'Unknown activity %s\', $activity));
        }

        return $this->map[$activity];
    }
}',
                '<?php
namespace Evp\DebugPenaltyBundle\Service;
use Evp\UserSurveillanceBundle\Scenarios;
class ActivityScenarioSubjectMapper
{
    private $map = [
        Scenarios::SCENARIO_CREDENTIALS_BRUTE_FORCE_VERY_STRONG_LINK => \'[Login Bruteforce][Very Strong]\',
    ];

    /**
     * @param string $activity
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function resolve($activity)
    {
        if (!isset($this->map[$activity])) {
            throw new \InvalidArgumentException(sprintf(\'Unknown activity %s\', $activity));
        }

        return $this->map[$activity];
    }
}'
            ],
            [
                '<?php
namespace Evp\UserSurveillanceBundle\Service;
use DateTime;
use Evp\UserSurveillanceBundle\Entity\AuditEvent;
use Evp\UserSurveillanceBundle\Entity\Relation;
use Evp\UserSurveillanceBundle\Entity\WebSession;
use Evp\UserSurveillanceBundle\Repository\AuditEventRepository;
use Evp\UserSurveillanceBundle\Repository\RelationRepository;
class RelatedEventsProvider
{
    private $relationRepository;
    private $eventRepository;

    public function __construct(
        RelationRepository $relationRepository,
        AuditEventRepository $eventRepository
    ) {
        $this->relationRepository = $relationRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * @param DateTime $date
     * @param WebSession $webSession
     * @param array $relationTypes
     * @param string $eventType
     *
     * @return AuditEvent[]
     */
    public function getRelatedEvents(DateTime $date, WebSession $webSession, array $relationTypes, $eventType)
    {
        /** @var AuditEvent[] $events */
        $events = [];

        /** @var Relation[] $relations */
        $relations = [];

        foreach ($relationTypes as $type) {
            $fetchedRelations = $this->relationRepository->findByTypeAndSessionIdA(
                $date,
                $type,
                $webSession->getSessionId()
            );

            foreach ($fetchedRelations as $fetchedRelation) {
                // allow only unique relations
                if (!isset($relations[$fetchedRelation->getId()])) {
                    $relations[$fetchedRelation->getId()] = $fetchedRelation;
                }
            }
        }

        $sessionIds[] = $webSession->getSessionId();
        foreach ($relations as $relation) {
            $sessionIds[] = $relation->getSessionIdB();
        }

        foreach ($sessionIds as $sid) {
            $fetchedEvents = $this->eventRepository->fetchEventsByTypeAndDateAndSessionId($date, $sid, $eventType);

            foreach ($fetchedEvents as $fetchedEvent) {
                $events[] = $fetchedEvent;
            }
        }

        return $events;
    }
}',
                '<?php
namespace Evp\UserSurveillanceBundle\Service;
use Evp\UserSurveillanceBundle\Entity\AuditEvent;
use Evp\UserSurveillanceBundle\Entity\Relation;
use Evp\UserSurveillanceBundle\Entity\WebSession;
use Evp\UserSurveillanceBundle\Repository\AuditEventRepository;
use Evp\UserSurveillanceBundle\Repository\RelationRepository;
class RelatedEventsProvider
{
    private $relationRepository;
    private $eventRepository;

    public function __construct(
        RelationRepository $relationRepository,
        AuditEventRepository $eventRepository
    ) {
        $this->relationRepository = $relationRepository;
        $this->eventRepository = $eventRepository;
    }

    /**
     * @param \DateTime $date
     * @param WebSession $webSession
     * @param array $relationTypes
     * @param string $eventType
     *
     * @return AuditEvent[]
     */
    public function getRelatedEvents(\DateTime $date, WebSession $webSession, array $relationTypes, $eventType)
    {
        /** @var AuditEvent[] $events */
        $events = [];

        /** @var Relation[] $relations */
        $relations = [];

        foreach ($relationTypes as $type) {
            $fetchedRelations = $this->relationRepository->findByTypeAndSessionIdA(
                $date,
                $type,
                $webSession->getSessionId()
            );

            foreach ($fetchedRelations as $fetchedRelation) {
                // allow only unique relations
                if (!isset($relations[$fetchedRelation->getId()])) {
                    $relations[$fetchedRelation->getId()] = $fetchedRelation;
                }
            }
        }

        $sessionIds[] = $webSession->getSessionId();
        foreach ($relations as $relation) {
            $sessionIds[] = $relation->getSessionIdB();
        }

        foreach ($sessionIds as $sid) {
            $fetchedEvents = $this->eventRepository->fetchEventsByTypeAndDateAndSessionId($date, $sid, $eventType);

            foreach ($fetchedEvents as $fetchedEvent) {
                $events[] = $fetchedEvent;
            }
        }

        return $events;
    }
}'
            ],
            [
                '<?php

namespace Evp\UserSurveillanceBundle\Entity;
use DateTime;
class Action
{
    const STATUS_PENDING = \'pending\';
    const STATUS_CANCELLED = \'cancelled\';
    const STATUS_PROCESSING = \'processing\';
    const STATUS_DONE = \'done\';

    /**
     * @var int
     */
    private $id;

    /**
     * @var WebSession
     */
    private $webSession;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $status;

    /**
     * @var DateTime
     */
    private $createdAt;

    /**
     * @var string
     */
    private $processorKey;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return WebSession
     */
    public function getWebSession()
    {
        return $this->webSession;
    }

    /**
     * @param WebSession $webSession
     *
     * @return $this
     */
    public function setWebSession($webSession)
    {
        $this->webSession = $webSession;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getProcessorKey()
    {
        return $this->processorKey;
    }

    /**
     * @param string $processorKey
     *
     * @return $this
     */
    public function setProcessorKey($processorKey)
    {
        $this->processorKey = $processorKey;

        return $this;
    }
}',
                '<?php

namespace Evp\UserSurveillanceBundle\Entity;
class Action
{
    const STATUS_PENDING = \'pending\';
    const STATUS_CANCELLED = \'cancelled\';
    const STATUS_PROCESSING = \'processing\';
    const STATUS_DONE = \'done\';

    /**
     * @var int
     */
    private $id;

    /**
     * @var WebSession
     */
    private $webSession;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @var string
     */
    private $processorKey;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return WebSession
     */
    public function getWebSession()
    {
        return $this->webSession;
    }

    /**
     * @param WebSession $webSession
     *
     * @return $this
     */
    public function setWebSession($webSession)
    {
        $this->webSession = $webSession;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getProcessorKey()
    {
        return $this->processorKey;
    }

    /**
     * @param string $processorKey
     *
     * @return $this
     */
    public function setProcessorKey($processorKey)
    {
        $this->processorKey = $processorKey;

        return $this;
    }
}'
            ],
            [
                '<?php
namespace My\Super\Feature;
use DateTime;
class Sample
{
    public function __construct(DateTime $b)
    {
        $this->b = $b;
        $this->a = new DateTime();
    }
}',
                '<?php
namespace My\Super\Feature;
class Sample
{
    public function __construct(\DateTime $b)
    {
        $this->b = $b;
        $this->a = new \DateTime();
    }
}'
            ],
            [
                '<?php
namespace My\Super\Feature;
use DateTime;
use My\Other\Cool\Space;
class Sample
{
    /**
     * @var DateTime
     */
    private $startDate;

    /**
     * @var Space
     */
    private $baba;

    /**
     * @return Space
     */
    public function getBaba()
    {
        return new Space();
    }

    /**
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }
}',
                '<?php
namespace My\Super\Feature;
class Sample
{
    /**
     * @var \DateTime
     */
    private $startDate;

    /**
     * @var \My\Other\Cool\Space
     */
    private $baba;

    /**
     * @return \My\Other\Cool\Space
     */
    public function getBaba()
    {
        return new \My\Other\Cool\Space();
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }
}'
            ],
            [
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasics;
class Sample
{
    public function doSomething()
    {
        /** @var Event\UserPositionEventData $positionData */
        $positionData = $this->positionDataRepository->findOneByEventData($eventData);
    }
}', null
            ],
            [
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasics;
use Wallet\AccountInfo\Sample as BaseSample;
class Sample extends BaseSample
{

}',
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasics;
class Sample extends \Wallet\AccountInfo\Sample
{

}'
            ],
            [
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasics;
use WebToPay\ApiBundle\Entity\Wallet\AccountInfo;
class Sample
{
    /**
     * @var Wallet\AccountInfo not persisted to database
     */
    protected $accountInfo;

    /**
     * Gets accountInfo
     *
     * @param AccountInfo $accountInfo
     *
     * @return $this
     */
    public function setAccountInfo($accountInfo)
    {
        $this->accountInfo = $accountInfo;

        return $this;
    }
}',
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasics;
class Sample
{
    /**
     * @var Wallet\AccountInfo not persisted to database
     */
    protected $accountInfo;

    /**
     * Gets accountInfo
     *
     * @param \WebToPay\ApiBundle\Entity\Wallet\AccountInfo $accountInfo
     *
     * @return $this
     */
    public function setAccountInfo($accountInfo)
    {
        $this->accountInfo = $accountInfo;

        return $this;
    }
}'
            ],
            [
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasics;
use Some\Another\Custom\Exception\SomeException;
use Some\Custom\Exception\InvalidArgumentException;
class Sample
{
    /**
     * @throws \Evp\Component\TextFilter\Exception
     * @throws \Exception
     * @throws InvalidArgumentException
     * @throws SomeException
     */
    public function sampleFunction()
    {
        if (true) {
            throw new \Evp\Component\TextFilter\Exception();
        } else {
            throw new \Exception("Some exception");
        }
        throw new InvalidArgumentException("Some exception");
    }
}',
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasics;
class Sample
{
    /**
     * @throws \Evp\Component\TextFilter\Exception
     * @throws \Exception
     * @throws \Some\Custom\Exception\InvalidArgumentException
     * @throws \Some\Another\Custom\Exception\SomeException
     */
    public function sampleFunction()
    {
        if (true) {
            throw new \Evp\Component\TextFilter\Exception();
        } else {
            throw new \Exception("Some exception");
        }
        throw new \Some\Custom\Exception\InvalidArgumentException("Some exception");
    }
}'
            ],
            [
                '<?php
namespace Paysera\RestrictionBundle\Exception;

class RestrictionException extends \Exception
{
}'
            ],
            [
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic;
use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\NamespacesAndUseStatementsFixer;
use Evp\Component\TextFilter\TextFilter;

class Sample
{
    /**
     * @var TextFilter
     */
    protected $textFilter;

    public function sampleFunction(NamespacesAndUseStatementsFixer $fixer)
    {
        $someConstant = Some\Custom\Ns\MyClass::CONSTANT;
        $value = new NamespacesAndUseStatementsFixer();
        $someConstantValue = NamespacesAndUseStatementsFixer::CONSTANT_VALUE;
        if ($someConstant instanceof NamespacesAndUseStatementsFixer) {
            return 0;
        }
    }
}',
                '<?php
namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic;

class Sample
{
    /**
     * @var \Evp\Component\TextFilter\TextFilter
     */
    protected $textFilter;

    public function sampleFunction(\Paysera\PhpCsFixerConfig\Fixer\PhpBasic\NamespacesAndUseStatementsFixer $fixer)
    {
        $someConstant = Some\Custom\Ns\MyClass::CONSTANT;
        $value = new \Paysera\PhpCsFixerConfig\Fixer\PhpBasic\NamespacesAndUseStatementsFixer();
        $someConstantValue = \Paysera\PhpCsFixerConfig\Fixer\PhpBasic\NamespacesAndUseStatementsFixer::CONSTANT_VALUE;
        if ($someConstant instanceof \Paysera\PhpCsFixerConfig\Fixer\PhpBasic\NamespacesAndUseStatementsFixer) {
            return 0;
        }
    }
}',
            ],
            [
                '<?php
namespace WebToPay\ApiBundle\Entity\PendingPayment;

/**
 * PasswordPendingPayment
 *
 * @author Vytautas Gimbutas <vytautas@gimbutas.net>
 * @package WebToPay\ApiBundle\Entity\PendingPayment
 */
class PasswordPendingPayment extends PendingPayment
{
}',
null
            ],
            [
                '<?php
namespace WebToPay\ApiBundle\Entity\PendingPayment;
use WebToPay\ApiBundle\Entity\PendingPayment;

class PasswordPendingPayment extends PendingPayment
{
    /**
     * @param PendingPayment $a
     */
    public function asdasd($a)
    {
    }
}',
                '<?php
namespace WebToPay\ApiBundle\Entity\PendingPayment;

class PasswordPendingPayment extends PendingPayment
{
    /**
     * @param \WebToPay\ApiBundle\Entity\PendingPayment $a
     */
    public function asdasd($a)
    {
    }
}'
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new NamespacesAndUseStatementsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_namespaces_and_use_statements';
    }
}
