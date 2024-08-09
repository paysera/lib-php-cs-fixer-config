<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\AssignmentsInConditionsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class AssignmentInConditionsFixerTest extends AbstractPayseraFixerTestCase
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
                while ($a = true) {
                    $a = false;
                }
                
                if ($a = true) { // TODO: "$a" - PhpBasic convention 3.7: We do not use assignments inside conditional statements
                    $a = false;
                }',
                '<?php
                while ($a = true) {
                    $a = false;
                }
                
                if ($a = true) {
                    $a = false;
                }'
            ],
            [
                '<?php
                if (($b = $a->get()) !== null && ($c = $b->get()) !== null) { // TODO: "$b" - PhpBasic convention 3.7: We do not use assignments inside conditional statements
                    $c->do();
                }
                if ($project = $this->findProject()) { // TODO: "$project" - PhpBasic convention 3.7: We do not use assignments inside conditional statements
                 
                }',
                '<?php
                if (($b = $a->get()) !== null && ($c = $b->get()) !== null) {
                    $c->do();
                }
                if ($project = $this->findProject()) {
                 
                }'
            ],
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new AssignmentsInConditionsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_feature_assignments_in_conditions';
    }
}
