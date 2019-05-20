<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\MethodNamingFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class MethodNamingFixerTest extends AbstractPayseraFixerTestCase
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
                        return $this->ready;
                    }
                    
                    private function isEqual()
                    {
                        return "a" === "b";
                    }
                    
                    private function isTokenSomething($token)
                    {
                        return $token->isSomething();
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
                    { // TODO: Question-type functions always return boolean (https://bit.ly/psg-methods)
                        return $this->object;
                    }
                    
                    private function hasRights()
                    { // TODO: Question-type functions always return boolean (https://bit.ly/psg-methods)
                        makeSomething();
                        throw new Exception();
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
                        return $this->object;
                    }
                    
                    private function hasRights()
                    {
                        makeSomething();
                        throw new Exception();
                    }
                }'
            ],
            [
                '<?php
                namespace Evp\Bundle\BankTransferBundle\Entity;
                class Sample
                {
                    public function backupNumber()
                    {
                        $this->setNumberBackup($this->getNumber());
                        $this->setNumber(null);
                    }
                }',
                null
            ],
            'Works with PHP 7 scalar return types' => [
                '<?php
                class Sample
                {
                    private function areListed(): int
                    { // TODO: Question-type functions always return boolean (https://bit.ly/psg-methods)
                        return $this->listed;
                    }
                }',
                '<?php
                class Sample
                {
                    private function areListed(): int
                    {
                        return $this->listed;
                    }
                }'
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
