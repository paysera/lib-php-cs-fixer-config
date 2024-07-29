<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\ClassNamingFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class ClassNamingFixerTest extends AbstractPayseraFixerTestCase
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
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;
                
                class EventNormalizer
                {
                
                }',
                null,
            ],
            [
                '<?php
                namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;
                
                class SomeServiceProvider
                {
                
                }',
                null,
            ],
            [
                '<?php

                namespace WebToPay\SmsCallbackBundle\Service;
                
                // TODO: "Page" - PhpBasic convention 2.5.2: For services suffix has to represent the job of that service
                
                class Page
                {
                
                }',
                '<?php

                namespace WebToPay\SmsCallbackBundle\Service;
                
                class Page
                {
                
                }',
            ],
            [
                '<?php

                namespace WebToPay\SmsCallbackBundle\Service;
                
                // TODO: "SmsTextManipulating" - PhpBasic convention 2.5.2: For services suffix has to represent the job of that service
                
                class SmsTextManipulating
                {
                
                }',
                '<?php

                namespace WebToPay\SmsCallbackBundle\Service;
                
                class SmsTextManipulating
                {
                
                }',
            ],
            [
                '<?php
                namespace WebToPay\CurrencyBundle\Service;
                                
                class CurrencyRepository implements CurrencyRepositoryInterface
                {
                
                }',
                null,
            ],
            [
                '<?php
                namespace WebToPay\ApiBundle\Service;
                
                use WebToPay\ApiBundle\Entity\Transaction;
                use Doctrine\ORM\EntityManager;
                use Psr\Log\LoggerInterface;

                // TODO: "CleanUpService" - PhpBasic convention 2.5.2: For services suffix has to represent the job of that service

                class CleanUpService
                {
                
                }',
                '<?php
                namespace WebToPay\ApiBundle\Service;
                
                use WebToPay\ApiBundle\Entity\Transaction;
                use Doctrine\ORM\EntityManager;
                use Psr\Log\LoggerInterface;

                class CleanUpService
                {
                
                }',
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new ClassNamingFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_code_style_class_naming';
    }
}
