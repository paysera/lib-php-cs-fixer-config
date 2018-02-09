<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TypeHintingFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class TypeHintingFixerTest extends AbstractFixerTestCase
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
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\SecurityContext;
                
                class OAuthApiWalletListener
                {
                    /**
                     * @var SecurityContext
                     */
                    protected $securityContext;
                
                    /**
                     * OAuthApiWalletListener constructor.
                     * @param SecurityContext $securityContext
                     */
                    public function __construct(SecurityContext $securityContext)
                    {
                        $this->securityContext = $securityContext;
                    } /* TODO: Class(Narrowest Interface): "SecurityContext(TokenStorageInterface)" - PhpBasic convention 3.18: We always type hint narrowest possible interface */
                
                    public function onKernelController()
                    {
                        $this->securityContext->getToken();
                    }
                }',
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\SecurityContext;
                
                class OAuthApiWalletListener
                {
                    /**
                     * @var SecurityContext
                     */
                    protected $securityContext;
                
                    /**
                     * OAuthApiWalletListener constructor.
                     * @param SecurityContext $securityContext
                     */
                    public function __construct(SecurityContext $securityContext)
                    {
                        $this->securityContext = $securityContext;
                    }
                
                    public function onKernelController()
                    {
                        $this->securityContext->getToken();
                    }
                }',
            ],
            [
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\ResultNormalizer;
                class Sample
                {
                    private $arg1;
                
                    public function __construct(ResultNormalizer $arg1)
                    {
                        $this->arg1 = $arg1;
                    }
                    
                    private function someFunction()
                    {
                        $this->arg1->methodC();
                    }
                }',
                null,
            ],
            [
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Doctrine\ORM\EntityManager;
                use Doctrine\ORM\OtherClass;
                class Sample
                {
                    private $arg1;
                
                    public function __construct(EntityManager $arg1, OtherClass $arg2)
                    {
                        $this->arg1 = $arg1;
                        $this->arg2 = $arg2;
                    }
                    
                    private function someFunction()
                    {
                        $this->arg1->methodC();
                    }
                }',
                null,
            ],
            [
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\ResultNormalizer;
                class Sample
                {
                    private $arg1;
                
                    public function __construct(ResultNormalizer $arg1)
                    {
                        $this->arg1 = $arg1;
                    } /* TODO: Class(Narrowest Interface): "ResultNormalizer(NormalizerInterface)" - PhpBasic convention 3.18: We always type hint narrowest possible interface */
                    
                    private function someFunction()
                    {
                        $this->arg1->methodA();
                    }
                }',
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\ResultNormalizer;
                class Sample
                {
                    private $arg1;
                
                    public function __construct(ResultNormalizer $arg1)
                    {
                        $this->arg1 = $arg1;
                    }
                    
                    private function someFunction()
                    {
                        $this->arg1->methodA();
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new TypeHintingFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_type_hinting';
    }
}
