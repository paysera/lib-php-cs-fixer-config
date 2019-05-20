<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature\VisibilityPropertiesFixer;
use Paysera\PhpCsFixerConfig\Tests\AbstractPayseraFixerTestCase;

final class VisibilityPropertiesFixerTest extends AbstractPayseraFixerTestCase
{
    /**
     * @param string $expected
     * @param null|string $input
     *
     * @dataProvider provideCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideCases()
    {
        return [
            [
                '<?php
                
                namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment;
                
                use PhpCsFixer\AbstractFixer;
                use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
                
                final class PhpDocContentsFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
                {
                    private function insertParamAnnotationWarning(Tokens $tokens, $docBlockIndex, $match, $warning)
                    {
                        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
                        $lines = $docBlock->getLines();
                        /** @var Line $annotation */
                        foreach ($lines as $index => &$annotation) {
                            $annotationContent = $annotation->getContent();
                            $replacement = preg_replace(\'#\\n#\', \' \', $annotationContent);
                            $lines[$index] = new Line(
                                $replacement . $warning . $this->whitespacesConfig->getLineEnding()
                            );
                            $tokens[$docBlockIndex]->setContent(implode(\'\', $lines));
                        }
                    }
                }',
                null,
            ],
            [
                '<?php

                namespace Evp\Bundle\CurrencyExchangeBundle\Validator\Constraints;
                
                use Symfony\Component\Validator\Constraint;
                
                class CurrenciesExchange extends Constraint
                {
                    public $messageCurrencies = \'validator.currencies_exchange.currencies_cannot_be_null\';
                
                    public $messageAmounts = \'validator.currencies_exchange.amounts_cannot_be_null\';
                
                    public function getTargets()
                    {
                        return self::CLASS_CONSTRAINT;
                    }
                }',
                null,
            ],
            [
                '<?php
                class Sample
                {
                    protected $something; // TODO: "$something" - PhpBasic convention 3.14.2: We prefer use private over protected properties
                    protected $a; // TODO: "$a" - PhpBasic convention 3.14.2: We prefer use private over protected properties
                }',
                '<?php
                class Sample
                {
                    protected $something;
                    protected $a;
                }'
            ],
            [
                '<?php
                class Sample
                {
                    public $something; // TODO: "$something" - PhpBasic convention 3.14.1: We don’t use public properties
                    protected $a; // TODO: "$a" - PhpBasic convention 3.14.2: We prefer use private over protected properties
                    public static $someSomething; // TODO: "$someSomething" - PhpBasic convention 3.14.1: We don’t use public properties
                }',
                '<?php
                class Sample
                {
                    public $something;
                    protected $a;
                    public static $someSomething;
                }'
            ],
            [
                '<?php
                class Sample
                {
                    public function __construct()
                    {
                        $this->a = "some dynamic property"; // TODO: "a" - PhpBasic convention 3.14: We must define as properties
                        $this->someProperty = "some dynamic property"; // TODO: "someProperty" - PhpBasic convention 3.14: We must define as properties
                    }
                    
                    public function someFunction()
                    {
                        $this->a = "dynamic property usage"; // TODO: "a" - PhpBasic convention 3.14: We must define as properties
                        $this->someProperty = "dynamic property usage"; // TODO: "someProperty" - PhpBasic convention 3.14: We must define as properties
                    }
                }',
                '<?php
                class Sample
                {
                    public function __construct()
                    {
                        $this->a = "some dynamic property";
                        $this->someProperty = "some dynamic property";
                    }
                    
                    public function someFunction()
                    {
                        $this->a = "dynamic property usage";
                        $this->someProperty = "dynamic property usage";
                    }
                }'
            ],
            [
                '<?php
                class Sample
                {
                    public function anotherFunction()
                    {
                        $foo = new Sample();
                        $foo->createProperty(\'hello\', \'something\');
                    }
                    
                    public function createProperty($name, $value){
                        $this->{$name} = $value; // TODO: "$name" - PhpBasic convention 3.14.1: We avoid dynamic properties
                    }
                }',
                '<?php
                class Sample
                {
                    public function anotherFunction()
                    {
                        $foo = new Sample();
                        $foo->createProperty(\'hello\', \'something\');
                    }
                    
                    public function createProperty($name, $value){
                        $this->{$name} = $value;
                    }
                }'
            ],
        ];
    }

    public function createFixerFactory()
    {
        $fixerFactory = parent::createFixerFactory();
        $fixerFactory->registerCustomFixers([
            new VisibilityPropertiesFixer(),
        ]);
        return $fixerFactory;
    }

    public function getFixerName()
    {
        return 'Paysera/php_basic_feature_visibility_properties';
    }
}
