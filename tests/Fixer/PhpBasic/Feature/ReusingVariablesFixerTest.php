<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\ReusingVariablesFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class ReusingVariablesFixerTest extends AbstractPayseraFixerTestCase
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
                '<?php class Sample
                {
                    public function thisIsWrong($number, $text, Request $request, SomeClass $someClass)
                    {
                        $localVariable = (int)$number;
                        $localVariable = (bool)$number; // TODO: "$localVariable" - PhpBasic convention 3.9: We do not change argument type or value
                        $localVariable = (string)$number; // TODO: "$localVariable" - PhpBasic convention 3.9: We do not change argument type or value
                        $number = (int)$number; // TODO: "$number" - PhpBasic convention 3.9: We do not change argument type or value
                        $text .= \' \'; // TODO: "$text" - PhpBasic convention 3.9: We do not change argument type or value
                        $document = $request->get(\'documentId\');
                        $document = $this->repository->find($document);
                        $someClass = new SomeClass(); // TODO: "$someClass" - PhpBasic convention 3.9: We do not change argument type or value
                    }
                }',
                '<?php class Sample
                {
                    public function thisIsWrong($number, $text, Request $request, SomeClass $someClass)
                    {
                        $localVariable = (int)$number;
                        $localVariable = (bool)$number;
                        $localVariable = (string)$number;
                        $number = (int)$number;
                        $text .= \' \';
                        $document = $request->get(\'documentId\');
                        $document = $this->repository->find($document);
                        $someClass = new SomeClass();
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    public function filter($value)
                    {
                        foreach ($this->filters as $filter) {
                            $value = $filter->filter($value); // TODO: "$value" - PhpBasic convention 3.9: We do not change argument type or value
                        }
                        return $value;
                    }
                }',
                '<?php class Sample
                {
                    public function filter($value)
                    {
                        foreach ($this->filters as $filter) {
                            $value = $filter->filter($value);
                        }
                        return $value;
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    private function addFixedCommission(Money $money, CommissionRule $rule, Money $commission)
                    {
                        if ($rule->getFixed() !== null) {
                            $commission = $commission->add(new Money($rule->getFixed(), $money->getCurrency())); // TODO: "$commission" - PhpBasic convention 3.9: We do not change argument type or value
                        }
                
                        return $commission;
                    }
                }',
                '<?php class Sample
                {
                    private function addFixedCommission(Money $money, CommissionRule $rule, Money $commission)
                    {
                        if ($rule->getFixed() !== null) {
                            $commission = $commission->add(new Money($rule->getFixed(), $money->getCurrency()));
                        }
                
                        return $commission;
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    private function sanitizeAccounts(ArrayCollection $accounts, Client $client)
                    {
                        $accounts = clone $accounts; // TODO: "$accounts" - PhpBasic convention 3.9: We do not change argument type or value
                    }
                }',
                '<?php class Sample
                {
                    private function sanitizeAccounts(ArrayCollection $accounts, Client $client)
                    {
                        $accounts = clone $accounts;
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    protected function getHash($number)
                    {
                        $code = strtoupper($number . $this->prefix . \'00\');
                        $digits = str_replace(
                            array(
                                 \'A\', \'B\', \'C\', \'D\', \'E\', \'F\', \'G\', \'H\', \'I\', \'J\', \'K\', \'L\', \'M\',
                                 \'N\', \'O\', \'P\', \'Q\', \'R\', \'S\', \'T\', \'U\', \'V\', \'W\', \'X\', \'Y\', \'Z\'
                            ),
                            array(
                                 \'10\', \'11\', \'12\', \'13\', \'14\', \'15\', \'16\', \'17\', \'18\', \'19\', \'20\', \'21\', \'22\',
                                 \'23\', \'24\', \'25\', \'26\', \'27\', \'28\', \'29\', \'30\', \'31\', \'32\', \'33\', \'34\', \'35\'
                            ),
                            $code
                        );
                
                        $m = 0;
                        for ($i = 0; $i < strlen($digits); $i++) {
                            $symbol = substr($digits, $i, 1);
                            $m = ($m * 10 + intval($symbol)) % 97;
                        }
                
                        $hash = 98 - $m;
                        return $hash < 10 ? \'0\' . $hash : (string) $hash; // TODO: "$hash" - PhpBasic convention 3.9: We do not change argument type or value
                    }
                }',
                '<?php class Sample
                {
                    protected function getHash($number)
                    {
                        $code = strtoupper($number . $this->prefix . \'00\');
                        $digits = str_replace(
                            array(
                                 \'A\', \'B\', \'C\', \'D\', \'E\', \'F\', \'G\', \'H\', \'I\', \'J\', \'K\', \'L\', \'M\',
                                 \'N\', \'O\', \'P\', \'Q\', \'R\', \'S\', \'T\', \'U\', \'V\', \'W\', \'X\', \'Y\', \'Z\'
                            ),
                            array(
                                 \'10\', \'11\', \'12\', \'13\', \'14\', \'15\', \'16\', \'17\', \'18\', \'19\', \'20\', \'21\', \'22\',
                                 \'23\', \'24\', \'25\', \'26\', \'27\', \'28\', \'29\', \'30\', \'31\', \'32\', \'33\', \'34\', \'35\'
                            ),
                            $code
                        );
                
                        $m = 0;
                        for ($i = 0; $i < strlen($digits); $i++) {
                            $symbol = substr($digits, $i, 1);
                            $m = ($m * 10 + intval($symbol)) % 97;
                        }
                
                        $hash = 98 - $m;
                        return $hash < 10 ? \'0\' . $hash : (string) $hash;
                    }
                }',
            ],
            [
                '<?php class Sample
                {
                    public function getAccountPermissions($clientId, $accountId, $type = null, $future = false, $needsReadPermission = true)
                    {
                        if ($type) {
                            switch ($type) {
                                case \'read\':
                                    $type = \'Evp\Bundle\BankPermissionBundle\Entity\Permission\ReadPermission\'; // TODO: "$type" - PhpBasic convention 3.9: We do not change argument type or value
                                    break;
                                case \'write\':
                                    $type = \'Evp\Bundle\BankPermissionBundle\Entity\Permission\WritePermission\'; // TODO: "$type" - PhpBasic convention 3.9: We do not change argument type or value
                                    break;
                                case \'sign\':
                                    $type = \'Evp\Bundle\BankPermissionBundle\Entity\Permission\SignPermission\'; // TODO: "$type" - PhpBasic convention 3.9: We do not change argument type or value
                                    break;
                                case \'administrate\':
                                    $type = \'Evp\Bundle\BankPermissionBundle\Entity\Permission\AdministratePermission\'; // TODO: "$type" - PhpBasic convention 3.9: We do not change argument type or value
                                    break;
                                default:
                                    throw new PermissionManagerException(\'Unknown permission type: \' . $type);
                            }
                        }
                        return new SoapResponse(
                            $this->getPermissionFacade()->getAccountPermissions($clientId, $accountId, $type, $future, $needsReadPermission)
                        );
                    }
                }',
                '<?php class Sample
                {
                    public function getAccountPermissions($clientId, $accountId, $type = null, $future = false, $needsReadPermission = true)
                    {
                        if ($type) {
                            switch ($type) {
                                case \'read\':
                                    $type = \'Evp\Bundle\BankPermissionBundle\Entity\Permission\ReadPermission\';
                                    break;
                                case \'write\':
                                    $type = \'Evp\Bundle\BankPermissionBundle\Entity\Permission\WritePermission\';
                                    break;
                                case \'sign\':
                                    $type = \'Evp\Bundle\BankPermissionBundle\Entity\Permission\SignPermission\';
                                    break;
                                case \'administrate\':
                                    $type = \'Evp\Bundle\BankPermissionBundle\Entity\Permission\AdministratePermission\';
                                    break;
                                default:
                                    throw new PermissionManagerException(\'Unknown permission type: \' . $type);
                            }
                        }
                        return new SoapResponse(
                            $this->getPermissionFacade()->getAccountPermissions($clientId, $accountId, $type, $future, $needsReadPermission)
                        );
                    }
                }',
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ReusingVariablesFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_reusing_variables';
    }
}
