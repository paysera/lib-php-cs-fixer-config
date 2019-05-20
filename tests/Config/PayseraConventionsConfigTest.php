<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Config;

use Paysera\PhpCsFixerConfig\Config\PayseraConventionsConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PayseraConventionsConfigTest extends TestCase
{

    public function testEnableMigrationModeWithIncompleteRuleSet()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageRegExp('/\'psr4\' => false,/');

        (new PayseraConventionsConfig())
            ->setDefaultFinder()
            ->setRules([
                'psr4' => true,
                'line_ending' => true,
            ])
            ->enableMigrationMode([
                'line_ending' => true,
            ])
            ->getRules()
        ;
    }

    public function testEnableMigrationMode()
    {
        $this->assertSame([
            'psr4' => false,
            'is_null' => ['use_yoda_style' => false],
            'line_ending' => false,
        ], (new PayseraConventionsConfig())
            ->setDefaultFinder()
            ->setRules([
                'psr4' => true,
                'is_null' => ['use_yoda_style' => false],
                'line_ending' => false,
            ])
            ->enableMigrationMode([
                'psr4' => false,
                'is_null' => true,
                'line_ending' => true,
                'no_homoglyph_names' => true,
            ])
            ->getRules()
        );
    }

    public function testWithoutMigrationMode()
    {
        $this->assertSame([
            'psr4' => true,
            'is_null' => ['use_yoda_style' => false],
            'line_ending' => false,
        ], (new PayseraConventionsConfig())
            ->setDefaultFinder()
            ->setRules([
                'psr4' => true,
                'is_null' => ['use_yoda_style' => false],
                'line_ending' => false,
            ])
            ->getRules()
        );
    }
}
