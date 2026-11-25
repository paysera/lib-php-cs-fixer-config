<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle\OneLineChainedMethodCallsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class OneLineChainedMethodCallsFixerTest extends AbstractPayseraFixerTestCase
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
            'two calls on one line' => [
                '<?php
class Sample
{
    public function run()
    {
        $builder->setA(1)
            ->setB(2)
        ;
    }
}',
                '<?php
class Sample
{
    public function run()
    {
        $builder->setA(1)->setB(2);
    }
}',
            ],
            'mock expectation on one line' => [
                '<?php
class Sample
{
    public function run()
    {
        $mock->expects($this->once())
            ->method(\'get\')
            ->willReturn(1)
        ;
    }
}',
                '<?php
class Sample
{
    public function run()
    {
        $mock->expects($this->once())->method(\'get\')->willReturn(1);
    }
}',
            ],
            'returned chain' => [
                '<?php
class Sample
{
    public function run()
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
        ;
    }
}',
                '<?php
class Sample
{
    public function run()
    {
        return $this->getEntityManager()->createQueryBuilder();
    }
}',
            ],
            'assigned chain after a property fetch' => [
                '<?php
class Sample
{
    public function run()
    {
        $rows = $this->entityManager->getRepository(Account::class)
            ->findAll()
        ;
    }
}',
                '<?php
class Sample
{
    public function run()
    {
        $rows = $this->entityManager->getRepository(Account::class)->findAll();
    }
}',
            ],
            'chain assigned to a property' => [
                '<?php
class Sample
{
    public function run()
    {
        $this->settings->limit = $config->getLimits()
            ->getDaily()
        ;
    }
}',
                '<?php
class Sample
{
    public function run()
    {
        $this->settings->limit = $config->getLimits()->getDaily();
    }
}',
            ],
            'static call followed by a chain' => [
                '<?php
class Sample
{
    public function run()
    {
        Factory::create()
            ->setA(1)
            ->setB(2)
        ;
    }
}',
                '<?php
class Sample
{
    public function run()
    {
        Factory::create()->setA(1)->setB(2);
    }
}',
            ],
            'chain after an array element and a property' => [
                '<?php
class Sample
{
    public function run()
    {
        $rows = $this->items[$key]->getProfile()
            ->settings->getName()
            ->format()
        ;
    }
}',
                '<?php
class Sample
{
    public function run()
    {
        $rows = $this->items[$key]->getProfile()->settings->getName()->format();
    }
}',
            ],
            'single call stays' => [
                '<?php
class Sample
{
    public function run()
    {
        $this->repository->save($entity);
    }
}',
                null,
            ],
            'call after a property fetch stays' => [
                '<?php
class Sample
{
    public function run()
    {
        $this->entityManager->flush();
    }
}',
                null,
            ],
            'chain inside an argument stays' => [
                '<?php
class Sample
{
    public function run()
    {
        $this->save($user->getProfile()->getName());
    }
}',
                null,
            ],
            'chain inside a string stays' => [
                '<?php
class Sample
{
    public function run()
    {
        $text = "{$user->getProfile()->getName()}";
        echo "{$user->getProfile()->getName()}";
    }
}',
                null,
            ],
            'chain with another operator stays' => [
                '<?php
class Sample
{
    public function run()
    {
        return $user->getProfile()->isActive() && $isEnabled;
    }
}',
                null,
            ],
            'chain already split stays' => [
                '<?php
class Sample
{
    public function run()
    {
        $builder
            ->setA(1)
            ->setB(2)
        ;
    }
}',
                null,
            ],
            'statement split over lines stays' => [
                '<?php
class Sample
{
    public function run()
    {
        $builder->setA(1)->setCallback(function () {
            return 1;
        });
    }
}',
                null,
            ],
            'chain in a for loop stays' => [
                '<?php
class Sample
{
    public function run()
    {
        for ($i = 0; $i < $list->getItems()->count(); $i++) {
        }
        for (; $list->getItems()->count(); ) {
        }
    }
}',
                null,
            ],
            'condition of a for loop split over lines stays' => [
                '<?php
class Sample
{
    public function run()
    {
        for (
            $i = 0;
            $list->getItems()->valid();
            $i++
        ) {
        }
    }
}',
                null,
            ],
            'statement sharing a line with a closure stays' => [
                '<?php
class Sample
{
    public function run()
    {
        $callback = function () { $builder->setA(1)->setB(2); };
    }
}',
                null,
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new OneLineChainedMethodCallsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_code_style_one_line_chained_method_calls';
    }
}
