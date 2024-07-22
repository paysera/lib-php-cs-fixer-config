<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Fixer\AbstractContextualTokenFixer;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\GroupSeparatorHelper;
use Paysera\PhpCsFixerConfig\Parser\Parser;
use Paysera\PhpCsFixerConfig\SyntaxParser\ClassStructureParser;
use Paysera\PhpCsFixerConfig\SyntaxParser\ImportedClassesParser;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionClass;
use Exception;

final class TypeHintingFixer extends AbstractContextualTokenFixer
{
    use ConfigurableFixerTrait;

    public const CONVENTION = 'PhpBasic convention 3.18: We always type hint narrowest possible interface';
    public const CONSTRUCT = '__construct';
    public const THIS = '$this';

    private array $exceptions;
    private ClassStructureParser $classStructureParser;

    public function __construct()
    {
        parent::__construct();

        $this->exceptions = ['EntityManager', 'Repository', 'Normalizer', 'Denormalizer'];
        $this->classStructureParser = new ClassStructureParser(
            new Parser(new GroupSeparatorHelper()),
            new ImportedClassesParser(),
        );
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'We always type hint narrowest possible interface which we use inside the function or class.',
            [
                new CodeSample(
                    <<<'PHP'
<?php

namespace Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Tests\Fixer\PhpBasic\Feature\Fixtures\SomeImplementation;

/*
interface InterfaceA
{
    public function a();
}

interface InterfaceB
{
    public function b();
    public function c();
}

class SomeImplementation implements InterfaceA, InterfaceB
{
    public function a() {}
    public function b() {}
    public function c() {}
}
*/

class Sample
{
    private $arg1;

    public function __construct(SomeImplementation $service)
    {
        // should be type hinted with InterfaceA instead of SomeImplementation // as we use only methods from InterfaceA
        $this->service = $service;
    }
    
    private function someFunction()
    {
        $this->service->a();
    }
}

PHP,
                ),
            ],
            null,
            'Paysera recommendation.',
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_feature_type_hinting';
    }

    public function isRisky(): bool
    {
        return true;
    }

    public function getPriority(): int
    {
        // before NamespacesAndUseStatementsFixer as it does not import classes themselves
        return 75;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function configure(array $configuration = null): void
    {
        if ($this->configuration['exceptions'] === true) {
            return;
        }
        if (isset($this->configuration['exceptions'])) {
            $this->exceptions = $this->configuration['exceptions'];
        }
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolver
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder(
                'exceptions',
                'Sets the exceptions for which usage of narrowest interface could be skipped.',
            ))
                ->setAllowedTypes(['array', 'bool'])
                ->getOption(),
        ]);
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
                $parameter->getTypeHintItem()->lastToken()->getNextToken(),
            );
            $firstToken->replaceWithTokens($this->buildTokens($suggestedTypeHint));
        }
    }

    private function analyseFunctionCalls(ContextualToken $firstToken): array
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

    private function buildTokens(string $fullClassName): array
    {
        $parts = explode('\\', ltrim($fullClassName, '\\'));
        $tokens = [];
        foreach ($parts as $part) {
            $tokens[] = new ContextualToken([T_NS_SEPARATOR, '\\']);
            $tokens[] = new ContextualToken([T_STRING, $part]);
        }

        return $tokens;
    }

    private function isBlacklisted(string $typeHintFullClass): bool
    {
        foreach ($this->exceptions as $exception) {
            if (strpos($typeHintFullClass, $exception) !== false) {
                return true;
            }
        }

        return false;
    }
}
