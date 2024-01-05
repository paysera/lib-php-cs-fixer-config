<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\SyntaxParser;

use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\ImportedClasses;
use Paysera\PhpCsFixerConfig\SyntaxParser\ImportedClassesParser;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;

class ImportedClassesParserTest extends TestCase
{
    /**
     * @dataProvider parseImportedClassesFromTokensDataProvider
     */
    public function testParseImportedClassesFromTokens(string $code, ImportedClasses $expectedImportedClasses)
    {
        $this->assertEquals(
            $expectedImportedClasses,
            (new ImportedClassesParser())->parseImportedClassesFromTokens(Tokens::fromCode($code))
        );
    }

    public function parseImportedClassesFromTokensDataProvider(): array
    {
        return [
            'empty' => [
                <<<'PHP'
<?php

class Sample
{

}

PHP
                ,
                (new ImportedClasses()),
            ],
            'only namespace' => [
                <<<'PHP'
<?php

namespace App;

class Sample
{

}

PHP
                ,
                (new ImportedClasses())->setCurrentNamespace('App'),
            ],
            'namespace and use' => [
                <<<'PHP'
<?php

namespace App;

use PhpCsFixer\AbstractFixer;

class Sample
{

}

PHP
                ,
                (new ImportedClasses())
                    ->setCurrentNamespace('App')
                    ->registerImport('AbstractFixer', 'PhpCsFixer\AbstractFixer')
                ,
            ],
            'namespace and use with alias' => [
                <<<'PHP'
<?php

namespace App;

use PhpCsFixer\AbstractFixer as BaseFixer;

class Sample
{

}

PHP
                ,
                (new ImportedClasses())
                    ->setCurrentNamespace('App')
                    ->registerImport('BaseFixer', 'PhpCsFixer\AbstractFixer')
                ,
            ],
            'multiple imports' => [
                <<<'PHP'
<?php

namespace App;

use PhpCsFixer\AbstractFixer as BaseFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;

class Sample
{

}

PHP
                ,
                (new ImportedClasses())
                    ->setCurrentNamespace('App')
                    ->registerImport('BaseFixer', 'PhpCsFixer\AbstractFixer')
                    ->registerImport(
                        'OrderedClassElementsFixer',
                        'PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer'
                    )
                ,
            ],
        ];
    }
}
