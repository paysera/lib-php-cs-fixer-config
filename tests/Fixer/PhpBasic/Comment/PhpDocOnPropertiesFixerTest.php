<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Comment;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocOnPropertiesFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class PhpDocOnPropertiesFixerTest extends AbstractPayseraFixerTestCase
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
class ClassWithPropertyButWithoutConstructor
{
    /**
     * @var string
     */
    private $foo;
}'
            ],
            [
                '<?php
class SomeClass
{
    private $someProp;
    private $anotherProp;
    
    public function __construct(
        array $autoCommit,
        array $otherAutoCommit
    ) {
        $this->someProp = $autoCommit;
        $this->anotherProp = $otherAutoCommit;
    }
}'
            ],
            [
                '<?php
class RefreshedTokenProvider extends GrantTypeTokenProvider
{
    private $someProp;
    private $anotherProp;
    
    public function __construct(
        NormalizerInterface $autoCommit,
        NormalizerInterface $otherAutoCommit
    ) {
        $this->someProp = $autoCommit;
        $this->anotherProp = $otherAutoCommit;
    }
}'
            ],
            [
                '<?php
class RefreshedTokenProvider extends GrantTypeTokenProvider
{
    private $someProp;
    
    public function __construct(
        AutoCommit $autoCommit
    ) {
        $this->someProp = $autoCommit;
    }
}'
            ],
            [
                '<?php
class RefreshedTokenProvider extends GrantTypeTokenProvider
{
    protected $autoCommit;
    
    public function __construct(
        AutoCommit $autoCommit,
        RefreshTokenRepository $refreshTokenRepository,
        ScopeExtensionCodeRepository $codeRepository
    ) {
        $this->autoCommit = $autoCommit;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->codeRepository = $codeRepository;
    }
}',
                '<?php
class RefreshedTokenProvider extends GrantTypeTokenProvider
{
    /**
     * @var \Evp\Component\Doctrine\AutoCommit
     */
    protected $autoCommit;
    
    public function __construct(
        AutoCommit $autoCommit,
        RefreshTokenRepository $refreshTokenRepository,
        ScopeExtensionCodeRepository $codeRepository
    ) {
        $this->autoCommit = $autoCommit;
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->codeRepository = $codeRepository;
    }
}',
            ],
            [
                '<?php
class AccessTokenFilter extends BaseFilter
{
    // TODO: "$clientType" - PhpBasic convention 4.3: Missing DocBlock
    protected $clientType;
    
}',
                '<?php
class AccessTokenFilter extends BaseFilter
{
    protected $clientType;
    
}',
            ],
            [
                '<?php
class InternalTransferHandler implements CommissionHandlerInterface
{
    protected $transferApiClient;
    // TODO: "$payerAccount" - PhpBasic convention 4.3: Missing DocBlock
    protected $payerAccount;
    // TODO: "$applicationClientId" - PhpBasic convention 4.3: Missing DocBlock
    protected $applicationClientId;
    // TODO: "$apiKey" - PhpBasic convention 4.3: Missing DocBlock
    protected $apiKey;
    protected $logger;

    public function __construct(
        TransferApiClient $transferApiClient,
        $payerAccount,
        $applicationClientId,
        $apiKey,
        LoggerInterface $logger
    ) {
        $this->transferApiClient = $transferApiClient;
        $this->payerAccount = $payerAccount;
        $this->applicationClientId = $applicationClientId;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }
}',
                '<?php
class InternalTransferHandler implements CommissionHandlerInterface
{
    protected $transferApiClient;
    protected $payerAccount;
    protected $applicationClientId;
    protected $apiKey;
    protected $logger;

    public function __construct(
        TransferApiClient $transferApiClient,
        $payerAccount,
        $applicationClientId,
        $apiKey,
        LoggerInterface $logger
    ) {
        $this->transferApiClient = $transferApiClient;
        $this->payerAccount = $payerAccount;
        $this->applicationClientId = $applicationClientId;
        $this->apiKey = $apiKey;
        $this->logger = $logger;
    }
}',
            ],
            [
                '<?php
class PosManager
{
    /**
     * @var PosProviderInterface[]
     */
    protected $posProviders = array();
    // TODO: "$posProvidersByPriority" - PhpBasic convention 4.3: Missing DocBlock
    protected $posProvidersByPriority = array();
    
    public function __construct(
        EntityManager $entityManager,
        EntityRepository $spotInfoRepository,
        SpotRepository $spotRepository
    ) {
        $this->entityManager = $entityManager;
        $this->spotInfoRepository = $spotInfoRepository;
        $this->spotRepository = $spotRepository;
    }
}',
                '<?php
class PosManager
{
    /**
     * @var PosProviderInterface[]
     */
    protected $posProviders = array();
    protected $posProvidersByPriority = array();
    
    public function __construct(
        EntityManager $entityManager,
        EntityRepository $spotInfoRepository,
        SpotRepository $spotRepository
    ) {
        $this->entityManager = $entityManager;
        $this->spotInfoRepository = $spotInfoRepository;
        $this->spotRepository = $spotRepository;
    }
}',
            ],
            [
                '<?php
class ApiController
{
    protected $tokenProvider;
    protected $entityManager;
    protected $permissionManager;
    
    /**
     * Constructs object
     *
     * @param TokenProvider $tokenProvider
     * @param EntityManager $entityManager
     * @param ContextAwarePermissionManager $permissionManager
     */
    public function __construct(
        TokenProvider $tokenProvider,
        EntityManager $entityManager,
        ContextAwarePermissionManager $permissionManager
    ) {
        $this->tokenProvider = $tokenProvider;
        $this->entityManager = $entityManager;
        $this->permissionManager = $permissionManager;
    }
    
}',
                '<?php
class ApiController
{
    /**
     * @var TokenProvider
     */
    protected $tokenProvider;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ContextAwarePermissionManager
     */
    protected $permissionManager;
    
    /**
     * Constructs object
     *
     * @param TokenProvider $tokenProvider
     * @param EntityManager $entityManager
     * @param ContextAwarePermissionManager $permissionManager
     */
    public function __construct(
        TokenProvider $tokenProvider,
        EntityManager $entityManager,
        ContextAwarePermissionManager $permissionManager
    ) {
        $this->tokenProvider = $tokenProvider;
        $this->entityManager = $entityManager;
        $this->permissionManager = $permissionManager;
    }
    
}',
            ],
            [
                '<?php
class MyClass
{
    private $property;
    
    public function __construct()
    {
        $this->property = new stdClass();
    }
}',
                '<?php
class MyClass
{
    /**
     * @var stdClass
     */
    private $property;
    
    public function __construct()
    {
        $this->property = new stdClass();
    }
}',
            ],
            [
                '<?php
class MyClass
{
    /**
     * @var Collection|Something[]
     */
    private $property;
    
    public function __construct()
    {
        $this->property = new ArrayCollection();
    }
}',
            ],
            [
                '<?php
class MyClass extends TestCase
{
    /**
     * @var SomeClass|SomeOtherClass
     */
    private $property;
    
    public function __construct()
    {
        $this->property = $this->createMock();
    }
}',
            ],
            [
                '<?php
final class MyClass
{
    private $config;
    private $innerService;
    private $service;
    
    public function __construct()
    {
        $this->config = [];
        $this->innerService = new InnerService();
        $this->service = new Service($this->innerService);
    }
}',
            ],
            [
                '<?php
class MyClass
{
    /**
     * @var array|Something[]
     */
    private $config;
    
    public function __construct()
    {
        $this->config = [];
    }
}',
            ],
            [
                '<?php
class MyClass
{
    /**
     * Some very important note about the property
     * 
     * @var Something
     */
    private $property;
    
    public function __construct(Something $property)
    {
        $this->property = $property;
    }
}',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new PhpDocOnPropertiesFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_comment_php_doc_on_properties';
    }
}
