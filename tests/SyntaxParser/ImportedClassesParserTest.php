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
    public function testParseImportedClassesFromTokens(Tokens $tokens, ImportedClasses $expectedImportedClasses): void
    {
        $importedClassesParser = new ImportedClassesParser();
        $importedClasses = $importedClassesParser->parseImportedClassesFromTokens($tokens);
        $this->assertEquals($expectedImportedClasses, $importedClasses);
    }

    public function parseImportedClassesFromTokensDataProvider()
    {
        return [
            'empty' => [
                Tokens::fromCode(
                    <<<'PHP'
<?php

class Sample
{

}

PHP
                ),
                (new ImportedClasses()),
            ],
            'only namespace' => [
                Tokens::fromCode(
                    <<<'PHP'
<?php

namespace App;

class Sample
{

}

PHP
                ),
                (new ImportedClasses())->setCurrentNamespace('App'),
            ],
            'namespace and use' => [
                Tokens::fromCode(
                    <<<'PHP'
<?php

namespace App;

use PhpCsFixer\AbstractFixer;

class Sample
{

}

PHP
                ),
                (new ImportedClasses())
                    ->setCurrentNamespace('App')
                    ->registerImport('AbstractFixer', 'PhpCsFixer\AbstractFixer')
                ,
            ],
            'namespace and use with alias' => [
                Tokens::fromCode(
                    <<<'PHP'
<?php

namespace App;

use PhpCsFixer\AbstractFixer as BaseFixer;

class Sample
{

}

PHP
                ),
                (new ImportedClasses())
                    ->setCurrentNamespace('App')
                    ->registerImport('BaseFixer', 'PhpCsFixer\AbstractFixer')
                ,
            ],
            'multiple imports' => [
                Tokens::fromCode(
                    <<<'PHP'
<?php

namespace App;

use PhpCsFixer\AbstractFixer as BaseFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;

class Sample
{

}

PHP
                ),
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
