<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Comment;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment\FluidInterfaceFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class FluidInterfaceFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @dataProvider provideCases
     */
    public function testFix(string $expected, string $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideCases(): array
    {
        return [
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param int $something
                     * @return $this
                     */
                    public function another($something)
                    {
                        return $this;
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param int $something
                     * @return Sample
                     */
                    public function another($something)
                    {
                        return $this;
                    }
                }',
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param int $something
                     * @return $this
                     */
                    public function another($something)
                    {
                        return $this;
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param int $something
                     * @return static
                     */
                    public function another($something)
                    {
                        return $this;
                    }
                }',
            ],
            [
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param int $something
                     * @return $this 
                     */
                    public function another($something)
                    {
                        return $this;
                    }
                }',
                '<?php
                namespace Some\Entity;
                class Sample
                {
                    /**
                     * @param int $something
                     */
                    public function another($something)
                    {
                        return $this;
                    }
                }',
            ],
            [
                '<?php
                namespace Evp\Bundle\BankSmsQueryBundle\Entity;

                class Query
                {
                    /**
                     * Set sender
                     *
                     * @param string $sender
                     *
                     * @return $this
                     */
                    public function setSender($sender)
                    {
                        $this->sender = $sender;
                        return $this;
                    }
                }',
                '<?php
                namespace Evp\Bundle\BankSmsQueryBundle\Entity;

                class Query
                {
                    /**
                     * Set sender
                     *
                     * @param string $sender
                     *
                     * @return \Evp\Bundle\BankSmsQueryBundle\Entity\Query
                     */
                    public function setSender($sender)
                    {
                        $this->sender = $sender;
                        return $this;
                    }
                }',

            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new FluidInterfaceFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_comment_fluid_interface';
    }
}
