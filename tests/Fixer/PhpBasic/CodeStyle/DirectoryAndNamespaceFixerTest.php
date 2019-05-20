<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\DirectoryAndNamespaceFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class DirectoryAndNamespaceFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php namespace Evp\Bundle\ExcelBundle\Data;',
            ],
            [
                '<?php namespace Paysera\Bundle\TransfermateBundle\Entity;',
            ],
            [
                '<?php namespace Evp\Bundle\CurrencyBundle\Controller;',
            ],
            [
                '<?php namespace Evp\Bundle\IncomingFundsProcessorBundle\Tests\Service;',
            ],
            [
                '<?php namespace Some\Invalid\Namespaces\Namings; // TODO: "Namespaces" - PhpBasic convention 2.7.1: We use singular for namespaces',
                '<?php namespace Some\Invalid\Namespaces\Namings;',
            ],
            [
                '<?php namespace Evp\Bundle\UserBundle\ServiceInterface\MergeProviderInterface; // TODO: "ServiceInterface" - PhpBasic convention 2.7.2: We do not make directories just for interfaces',
                '<?php namespace Evp\Bundle\UserBundle\ServiceInterface\MergeProviderInterface;',
            ],
            [
                '<?php namespace Evp\Bundle\UserBundle\UserManager; // TODO: "UserManager" - PhpBasic convention 2.7.3: We use abstractions for namespaces',
                '<?php namespace Evp\Bundle\UserBundle\UserManager;',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new DirectoryAndNamespaceFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_directory_and_namespace';
    }
}
