<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\StaticMethodsFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;
use PhpCsFixer\FixerFactory;

final class StaticMethodsFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @dataProvider provideCases
     * @dataProvider provideAttributeCases
     */
    public function testFix(string $expected, ?string $input)
    {
        $this->doTest($expected, $input);
    }

    public static function provideCases(): iterable
    {
        yield 'Non static data provider' => [
            'expected' =>
                '<?php class MyTest
                {
                    /**
                     * @dataProvider testDataProvider
                     */
                    public function runTest(string $text): void 
                    {
                        $this->assertSame(\'data\', $text);
                    }
                
                    public function testDataProvider(): array // TODO: "testDataProvider" - PhpBasic convention: Static methods, https://github.com/paysera/php-style-guide?tab=readme-ov-file#static-methods
                    {
                        return [[\'data\']];
                    }
                }'
            ,
            'input' =>
                '<?php class MyTest
                {
                    /**
                     * @dataProvider testDataProvider
                     */
                    public function runTest(string $text): void 
                    {
                        $this->assertSame(\'data\', $text);
                    }
                
                    public function testDataProvider(): array
                    {
                        return [[\'data\']];
                    }
                }'
            ,
        ];
        yield 'Non static data provider with out provider naming' => [
            'expected' =>
                '<?php class MyTest
                {
                    /**
                     * @dataProvider getCases
                     */
                    public function runTest(string $text): void 
                    {
                        $this->assertSame(\'data\', $text);
                    }
                
                    public function getCases(): array // TODO: "getCases" - PhpBasic convention: Static methods, https://github.com/paysera/php-style-guide?tab=readme-ov-file#static-methods
                    {
                        return [[\'data\']];
                    }
                }'
            ,
            'input' =>
                '<?php class MyTest
                {
                    /**
                     * @dataProvider getCases
                     */
                    public function runTest(string $text): void 
                    {
                        $this->assertSame(\'data\', $text);
                    }
                
                    public function getCases(): array
                    {
                        return [[\'data\']];
                    }
                }'
            ,
        ];

        yield 'Static data provider' => [
            'expected' =>
                '<?php class MyTest
                {
                    /**
                     * @dataProvider testDataProvider
                     */
                    public function runTest(string $text): void 
                    {
                        $this->assertSame(\'data\', $text);
                    }
                
                    public static function testDataProvider(): array
                    {
                        return [[\'data\']];
                    }
                }'
            ,
            'input' => null,
        ];

        yield 'Not a data provider' => [
            'expected' =>
                '<?php class MyTest
                {
                    public function runTest(string $text): void 
                    {
                        $this->assertSame(\'data\', $text);
                    }
                
                    public static function testDataProvider(): array
                    {
                        return [[\'data\']];
                    }
                }'
            ,
            'input' => null,
        ];

    }

    public static function provideAttributeCases(): iterable
    {
        if (PHP_VERSION_ID < 80000) {
            return;
        }

        yield 'Non static attribute data provider' => [
            'expected' =>
                '<?php class MyTest
                {
                    #[DataProvider(\'getCases\')]
                    public function test(): void 
                    {
                        $this->assertTrue(true);
                    }
                
                    public function getCases(): array // TODO: "getCases" - PhpBasic convention: Static methods, https://github.com/paysera/php-style-guide?tab=readme-ov-file#static-methods
                    {
                        return [[\'data\']];
                    }
                }'
            ,
            'input' =>
                '<?php class MyTest
                {
                    #[DataProvider(\'getCases\')]
                    public function test(): void 
                    {
                        $this->assertTrue(true);
                    }
                
                    public function getCases(): array
                    {
                        return [[\'data\']];
                    }
                }'
            ,
        ];

        yield 'Static attribute data provider' => [
            'expected' =>
                '<?php class MyTest
                {
                    #[DataProvider(\'getCases\')]
                    public function test(): void 
                    {
                        $this->assertTrue(true);
                    }
                
                    public static function getCases(): array
                    {
                        return [[\'data\']];
                    }
                }'
            ,
            'input' => null,
        ];

        yield 'Multiple attribute data providers' => [
            'expected' =>
                '<?php class MyTest
                {
                    #[DataProvider(\'provider1\')]
                    #[DataProvider(\'provider2\')]
                    public function test(): void 
                    {
                        $this->assertTrue(true);
                    }
                
                    public function provider1(): array // TODO: "provider1" - PhpBasic convention: Static methods, https://github.com/paysera/php-style-guide?tab=readme-ov-file#static-methods
                    {
                        return [[\'data\']];
                    }
                
                    public function provider2(): array // TODO: "provider2" - PhpBasic convention: Static methods, https://github.com/paysera/php-style-guide?tab=readme-ov-file#static-methods
                    {
                        return [[\'data\']];
                    }
                }'
            ,
            'input' =>
                '<?php class MyTest
                {
                    #[DataProvider(\'provider1\')]
                    #[DataProvider(\'provider2\')]
                    public function test(): void 
                    {
                        $this->assertTrue(true);
                    }
                
                    public function provider1(): array
                    {
                        return [[\'data\']];
                    }
                
                    public function provider2(): array
                    {
                        return [[\'data\']];
                    }
                }'
            ,
        ];

        yield 'Mixed docblock and attribute data providers' => [
            'expected' =>
                '<?php class MyTest
                {
                    /**
                     * @dataProvider docBlockProvider
                     */
                    #[DataProvider(\'attributeProvider\')]
                    public function test(): void 
                    {
                        $this->assertTrue(true);
                    }
                
                    public function docBlockProvider(): array // TODO: "docBlockProvider" - PhpBasic convention: Static methods, https://github.com/paysera/php-style-guide?tab=readme-ov-file#static-methods
                    {
                        return [[\'data\']];
                    }
                
                    public function attributeProvider(): array // TODO: "attributeProvider" - PhpBasic convention: Static methods, https://github.com/paysera/php-style-guide?tab=readme-ov-file#static-methods
                    {
                        return [[\'data\']];
                    }
                }'
            ,
            'input' =>
                '<?php class MyTest
                {
                    /**
                     * @dataProvider docBlockProvider
                     */
                    #[DataProvider(\'attributeProvider\')]
                    public function test(): void 
                    {
                        $this->assertTrue(true);
                    }
                
                    public function docBlockProvider(): array
                    {
                        return [[\'data\']];
                    }
                
                    public function attributeProvider(): array
                    {
                        return [[\'data\']];
                    }
                }'
            ,
        ];
    }

    public function createFixerFactory(): FixerFactory
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new StaticMethodsFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName(): string
    {
        return 'Paysera/php_basic_feature_static_methods';
    }
}
