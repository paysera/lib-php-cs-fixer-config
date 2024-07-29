<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests;

use PhpCsFixer\FixerFactory;
use PhpCsFixer\RuleSet\RuleSet;
use UnexpectedValueException;

use function count;
use function sprintf;


abstract class AbstractPayseraFixerTestCase extends AbstractFixerTestCase
{
    abstract protected function getFixerName(): string;

    protected function createFixerFactory(): FixerFactory
    {
        return (new FixerFactory())->registerBuiltInFixers();
    }

    protected function createFixer()
    {
        $fixerClassName = $this->getFixerClassName();
        return $this->fixer = new $fixerClassName();
    }

    private function getFixerClassName(): string
    {
        try {
            $fixers = $this
                ->createFixerFactory()
                ->useRuleSet(new RuleSet([$this->getFixerName() => true]))
                ->getFixers()
            ;
        } catch (UnexpectedValueException $exception) {
            throw new UnexpectedValueException(
                'Cannot determine fixer class, perhaps you forget to override `getFixerName` or `createFixerFactory` method?',
                0,
                $exception,
            );
        }
        if (1 !== count($fixers)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Determine fixer class should result in one fixer, got "%d". Perhaps you configured the fixer to "false" ?',
                    count($fixers),
                ),
            );
        }

        return get_class($fixers[0]);
    }
}
