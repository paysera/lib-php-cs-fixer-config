<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\TypeHintingFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class TypeHintingFixerTest extends AbstractPayseraFixerTestCase
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
                class ClassWithConstructorWithoutParameters
                {
                    public function __construct()
                    {
                    }
                }',
            ],
            [
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\SecurityContext;
                
                class OAuthApiWalletListener
                {
                    private $securityContext;
                
                    public function __construct(\Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\TokenStorageInterface $securityContext)
                    {
                        $this->securityContext = $securityContext;
                    }
                
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
                    private $securityContext;
                
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
                use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\SecurityContext as Context;
                
                class OAuthApiWalletListener
                {
                    private $securityContext;
                
                    public function __construct(\Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\TokenStorageInterface $securityContext)
                    {
                        $this->securityContext = $securityContext;
                    }
                
                    public function onKernelController()
                    {
                        $this->securityContext->getToken();
                    }
                }',
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\SecurityContext as Context;
                
                class OAuthApiWalletListener
                {
                    private $securityContext;
                
                    public function __construct(Context $securityContext)
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
                
                class OAuthApiWalletListener
                {
                    private $securityContext;
                
                    public function __construct(\Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\TokenStorageInterface $securityContext)
                    {
                        $this->securityContext = $securityContext;
                    }
                
                    public function onKernelController()
                    {
                        $this->securityContext->getToken();
                    }
                }',
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                
                class OAuthApiWalletListener
                {
                    private $securityContext;
                
                    public function __construct(\Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\SecurityContext $securityContext)
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
            ],
            [
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;
                use Doctrine\ORM\EntityManager;
                class Sample
                {
                    private $arg1;
                
                    public function __construct(EntityManager $arg1)
                    {
                        $this->arg1 = $arg1;
                    }
                    
                    private function someFunction()
                    {
                        $this->arg1->methodC();
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
                        $this->arg1->methodA();
                    }
                }',
            ],
            [
                '<?php
                namespace App;
                use PhpCsFixer\Fixer\ConfigurableFixerInterface;
                use PhpCsFixer\Fixer\DefinedFixerInterface;
                use PhpCsFixer\Fixer\FixerInterface;
                class Sample
                {
                    private $innerFixer;
                
                    public function __construct(FixerInterface $innerFixer)
                    {
                        $this->innerFixer = $innerFixer;
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
