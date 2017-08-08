<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Comment;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\PhpDocOnPropertiesFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class PhpDocOnPropertiesFixerTest extends AbstractFixerTestCase
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
