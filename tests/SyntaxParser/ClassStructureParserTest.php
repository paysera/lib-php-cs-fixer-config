<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\SyntaxParser;

use Paysera\PhpCsFixerConfig\Parser\ContextualTokenBuilder;
use Paysera\PhpCsFixerConfig\Parser\GroupSeparatorHelper;
use Paysera\PhpCsFixerConfig\Parser\Parser;
use Paysera\PhpCsFixerConfig\SyntaxParser\ClassStructureParser;
use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\ClassStructure;
use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\FunctionStructure;
use Paysera\PhpCsFixerConfig\SyntaxParser\Entity\ParameterStructure;
use Paysera\PhpCsFixerConfig\SyntaxParser\ImportedClassesParser;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;

class ClassStructureParserTest extends TestCase
{

    /**
     * Tests only functionally with real code. Also does not assert anything about tokens themselves, just the
     * structure and the string contents
     *
     * @param ClassStructure|null $expected
     * @param string $input
     *
     * @dataProvider provider
     */
    public function testParseClassStructure($expected, string $input)
    {
        $classStructureParser = new ClassStructureParser(
            new Parser(new GroupSeparatorHelper()),
            new ImportedClassesParser()
        );
        $contextualTokenBuilder = new ContextualTokenBuilder();
        $contextualToken = $contextualTokenBuilder->buildFromTokens(Tokens::fromCode($input));

        $classStructure = $classStructureParser->parseClassStructure($contextualToken);

        $this->assertSame($expected->getName(), $classStructure->getName());
        $methods = $classStructure->getMethods();
        $this->assertCount(count($expected->getMethods()), $methods);
        foreach ($expected->getMethods() as $i => $expectedMethod) {
            $method = $methods[$i];
            $this->assertSame($expectedMethod->getName(), $method->getName());
            $this->assertSame($expectedMethod->getKeywords(), $method->getKeywords());
            $this->assertEquals($expectedMethod->getParameters(), array_map(function(ParameterStructure $parameter) {
                return $parameter->setTypeHintItem(null);
            }, $method->getParameters()));
        }
    }

    public function provider()
    {
        return [
            [
                (new ClassStructure())
                    ->setName('A')
                    ->setMethods([
                        (new FunctionStructure())
                            ->setName('method')
                            ->setKeywords(['public'])
                            ->setParameters([
                                (new ParameterStructure())
                                    ->setName('$code')
                                    ->setTypeHintContent('string')
                                ,
                                (new ParameterStructure())
                                    ->setName('$a')
                                    ->setTypeHintContent('\\App\\Something')
                                    ->setTypeHintFullClass('App\\Something')
                                    ->setDefaultValue('null')
                                ,
                                (new ParameterStructure())
                                    ->setName('$something')
                                    ->setDefaultValue('"a"')
                                ,
                            ])
                        ,
                    ])
                ,
                '<?php
                class A
                {
                    public function method(string $code, \App\Something $a = null, $something = "a")
                    {
                        echo $code;
                    }
                }
                ',
            ],
        ];
    }
}
