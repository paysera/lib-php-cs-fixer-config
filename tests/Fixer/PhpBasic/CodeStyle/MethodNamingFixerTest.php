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
                    /**
                     * @param string $string
                     * @return bool
                     */
                    private function validateFunctionName($string)
                    { // TODO: "validateFunctionName" - PhpBasic convention 2.5.5: We use prefix - has, is, can for bool functions
                        return preg_match(\'#^__[^_]#\', $string) !== 0
                            || (!(preg_match(\'|[^a-zA-Z]|\', substr($string, 1)) > 0)
                            && !(preg_match(\'/^[a-z]/\', $string) === 0))
                        ;
                    }
                }',
                '<?php
                class Sample
                {
                    /**
                     * @param string $string
                     * @return bool
                     */
                    private function validateFunctionName($string)
                    {
                        return preg_match(\'#^__[^_]#\', $string) !== 0
                            || (!(preg_match(\'|[^a-zA-Z]|\', substr($string, 1)) > 0)
                            && !(preg_match(\'/^[a-z]/\', $string) === 0))
                        ;
                    }
                }',
            ],
            [
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    { // TODO: "sampleFunction" - PhpBasic convention 2.5.5: We use prefix - has, is, can for bool functions
                        return true;
                    }
                    
                    /**
                     * @return bool
                     */
                    private function anotherFunction()
                    { // TODO: "anotherFunction" - PhpBasic convention 2.5.5: We use prefix - has, is, can for bool functions
                    }
                }',
                '<?php
                class Sample
                {
                    private function sampleFunction()
                    {
                        return true;
                    }
                    
                    /**
                     * @return bool
                     */
                    private function anotherFunction()
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
