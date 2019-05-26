<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\AbstractContextualTokenFixer;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\GroupSeparatorHelper;
use Paysera\PhpCsFixerConfig\Parser\Parser;
use Paysera\PhpCsFixerConfig\SyntaxParser\ClassStructureParser;
use Paysera\PhpCsFixerConfig\SyntaxParser\ImportedClassesParser;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionClass;
use Exception;

final class TypeHintingFixer extends AbstractContextualTokenFixer
{
    const CONVENTION = 'PhpBasic convention 3.18: We always type hint narrowest possible interface';
    const CONSTRUCT = '__construct';
    const THIS = '$this';

    private $exceptions;
    private $classStructureParser;

    public function __construct()
    {
        parent::__construct();
        $this->exceptions = ['EntityManager', 'Repository', 'Normalizer', 'Denormalizer'];
        $this->classStructureParser = new ClassStructureParser(
            new Parser(new GroupSeparatorHelper()),
            new ImportedClassesParser()
        );
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            'We always type hint narrowest possible interface which we use inside the function or class.',
            [
                new CodeSample(
                    '<?php
                        class NormalizerInterface
                        {
                            public function methodA();
                        }
                        
                        class DenormalizerInterface
                        {
                            public function methodB();
                        }
                        
                        class ResultNormalizer implements NormalizerInterface, DenormalizerInterface
                        {
                            public function methodA()
                            {
                                return true;
                            }
                            
                            public function methodB()
                            {
                                return true;
                            }
                        }
                    
                        class Sample
                        {
                            $private $arg1;
                        
                            public function __construct(ResultNormalizer $arg1)
                            {
                                $this->arg1 = $arg1;
                            }
                            
                            private function someFunction()
                            {
                                $this->arg1->methodA();
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_type_hinting';
    }

    public function isRisky()
    {
        return true;
    }

    public function getPriority()
    {
        // before NamespacesAndUseStatementsFixer as it does not import classes themselves
        return 75;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        if ($this->configuration['exceptions'] === true) {
            return;
        }
        if (isset($this->configuration['exceptions'])) {
            $this->exceptions = $this->configuration['exceptions'];
        }
    }

    protected function createConfigurationDefinition()
    {
        $options = new FixerOptionBuilder(
            'exceptions',
            'Sets the exceptions for which usage of narrowest interface could be skipped.'
        );

        $options = $options
            ->setAllowedTypes(['array', 'bool'])
            ->getOption()
        ;

        return new FixerConfigurationResolverRootless('exceptions', [$options], $this->getName());
    }

    protected function applyFixOnContextualToken(ContextualToken $token)
    {
        $classStructure = $this->classStructureParser->parseClassStructure($token);
        if ($classStructure === null) {
            return;
        }

        $constructor = $classStructure->getConstructorMethod();
        if ($constructor === null) {
            return;
        }

        $calls = $this->analyseFunctionCalls($token);

        foreach ($constructor->getParameters() as $parameter) {
            $typeHintFullClass = $parameter->getTypeHintFullClass();
            if ($typeHintFullClass === null) {
                continue;
            }

            $propertyCalls = $calls[$parameter->getName()] ?? [];
            if (count($propertyCalls) === 0) {
                continue;
            }

            if ($this->isBlacklisted($typeHintFullClass)) {
                continue;
            }

            $suggestedTypeHint = $this->suggestTypeHint($typeHintFullClass, $propertyCalls);
            if ($suggestedTypeHint === null) {
                continue;
            }

            $firstToken = $parameter->getTypeHintItem()->firstToken()->setNextContextualToken(
                $parameter->getTypeHintItem()->lastToken()->getNextToken()
            );
            $firstToken->replaceWithTokens($this->buildTokens($suggestedTypeHint));
        }
    }

    private function analyseFunctionCalls(ContextualToken $firstToken)
    {
        $calls = [];
        $token = $firstToken;
        while ($token !== null) {
            if ($token->getContent() === '->') {
                $variableName = '$' . ltrim($token->previousNonWhitespaceToken()->getContent(), '$');
                $functionNameToken = $token->nextNonWhitespaceToken();
                if ($functionNameToken->nextNonWhitespaceToken()->getContent() === '(') {
                    $calls[$variableName][] = $functionNameToken->getContent();
                }
            }

            $token = $token->getNextToken();
        }

        return $calls;
    }

    private function suggestTypeHint(string $fullTypeHint, array $propertyCalls)
    {
        try {
            $reflectionClass = new ReflectionClass($fullTypeHint);
            $interfaces = $reflectionClass->getInterfaces();
        } catch (Exception $exception) {
            $interfaces = [];
        }

        if (count($interfaces) === 0) {
            return null;
        }

        foreach (array_reverse($interfaces) as $interface) {
            $methods = [];
            foreach ($interface->getMethods() as $reflectionMethod) {
                $methods[] = $reflectionMethod->getName();
            }

            if (count(array_diff($propertyCalls, $methods)) === 0) {
                return $interface->getName();
            }
        }

        return null;
    }

    private function buildTokens(string $fullClassName)
    {
        $parts = explode('\\', ltrim($fullClassName, '\\'));
        $tokens = [];
        foreach ($parts as $part) {
            $tokens[] = new ContextualToken([T_NS_SEPARATOR, '\\']);
            $tokens[] = new ContextualToken([T_STRING, $part]);
        }
        return $tokens;
    }

    private function isBlacklisted(string $typeHintFullClass)
    {
        foreach ($this->exceptions as $exception) {
            if (strpos($typeHintFullClass, $exception) !== false) {
                return true;
            }
        }

        return false;
    }
}
