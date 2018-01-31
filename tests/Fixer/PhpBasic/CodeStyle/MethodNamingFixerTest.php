<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\MethodNamingFixer;
use PhpCsFixer\Test\AbstractFixerTestCase;

final class MethodNamingFixerTest extends AbstractFixerTestCase
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

                    private function hash()
                    {
                        return hash(\'saads\');
                    }
                }',
                null
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @return bool
                     */
                    private function blaBla()
                    {
                        return true;
                    }
                }',
                null
            ],
            [
                '<?php
                class Sample
                {
                    private function canDoSomething()
                    {
                        return true;
                    }

                    /**
                     * @return bool
                     */
                    private function isReady()
                    {
                    }
                }',
                null
            ],
            [
                '<?php
                class Sample
                {
                    /**
                     * @return SomeObject
                     */
                    private function areListed()
                    { // TODO: "areListed" - PhpBasic convention 2.5.5: We use prefix - has, is, can for bool functions
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @return SomeObject
                     */
                    private function areListed()
                    {
                    }
                }'
            ],
            [
                '<?php
                namespace Evp\Bundle\BankTransferBundle\Entity;
                class Sample
                {
                    public function backupNumber()
                    { // TODO: "backupNumber" - PhpBasic convention 2.5.5: Invalid entity function name
                        $this->setNumberBackup($this->getNumber());
                        $this->setNumber(null);
                    }
                }',
                '<?php
                namespace Evp\Bundle\BankTransferBundle\Entity;
                class Sample
                {
                    public function backupNumber()
                    {
                        $this->setNumberBackup($this->getNumber());
                        $this->setNumber(null);
                    }
                }'
            ],
            [
                '<?php
                namespace Paysera\RestrictionBundle\Entity;
                
                class Sample
                {
                    public function __construct()
                    {
                    }
                    
                    public function getStatuses()
                    {
                    }
                    
                    public function addStatus()
                    {
                    }
                    
                    public function setType()
                    {
                    }
                    
                    public function markAsWaitingFunds()
                    {
                    }
                    
                    public function removeBankAccount()
                    {
                    }
                    
                    public function isFinalStatus()
                    {
                    }
                    
                    public function areLocalBanksIncluded()
                    {
                    }
                    
                    public function hasEditPermission()
                    {
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
            new MethodNamingFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_code_style_method_naming';
    }
}
