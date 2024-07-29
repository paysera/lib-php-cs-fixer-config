<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests;

use Exception;
use InvalidArgumentException;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\AbstractProxyFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DeprecatedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\Whitespace\SingleBlankLineAtEofFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionInterface;
use PhpCsFixer\FixerDefinition\CodeSampleInterface;
use PhpCsFixer\FixerDefinition\FileSpecificCodeSampleInterface;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSampleInterface;
use PhpCsFixer\Linter\CachingLinter;
use PhpCsFixer\Linter\Linter;
use PhpCsFixer\Linter\LinterInterface;
use PhpCsFixer\Linter\ProcessLinter;
use PhpCsFixer\Preg;
use PhpCsFixer\StdinFileInfo;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;
use PhpCsFixer\Tokenizer\Token;

use function array_slice;
use function count;
use function in_array;
use function is_string;
use function sprintf;

use const PHP_VERSION_ID;

abstract class AbstractFixerTestCase extends TestCase
{
    /**
     * @var FixerInterface|ConfigurableFixerInterface|null
     */
    protected ?FixerInterface $fixer = null;
    protected ?LinterInterface $linter = null;

    private array $allowedRequiredOptions = [
        'header_comment' => ['header' => true],
    ];

    // do not modify this structure without prior discussion
    private array $allowedFixersWithoutDefaultCodeSample = [
        'general_phpdoc_annotation_remove' => true,
        'general_phpdoc_tag_rename' => true,
    ];

    abstract protected function createFixer();

    final public function testIsRisky()
    {
        if ($this->fixer->isRisky()) {
            self::assertValidDescription($this->fixer->getName(), 'risky description', $this->fixer->getDefinition()->getRiskyDescription());
        } else {
            self::assertNull($this->fixer->getDefinition()->getRiskyDescription(), \sprintf('[%s] Fixer is not risky so no description of it expected.', $this->fixer->getName()));
        }

        if ($this->fixer instanceof AbstractProxyFixer) {
            return;
        }

        $reflection = new ReflectionMethod($this->fixer, 'isRisky');

        // If fixer is not risky then the method `isRisky` from `AbstractFixer` must be used
        self::assertSame(
            !$this->fixer->isRisky(),
            AbstractFixer::class === $reflection->getDeclaringClass()->getName()
        );
    }

    final public function testFixerDefinitions()
    {
        $fixerName = $this->fixer->getName();

        if ($fixerName == 'my_test_fixer') {
            $this->addToAssertionCount(1); // not applied to the my_test_fixer
            return;
        }

        $definition = $this->fixer->getDefinition();
        $fixerIsConfigurable = $this->fixer instanceof ConfigurableFixerInterface;

        self::assertValidDescription($fixerName, 'summary', $definition->getSummary());
        if (null !== $definition->getDescription()) {
            if ($fixerName == 'strict_param') {
                $this->addToAssertionCount(1); // not applied to the strict_param
                return;
            }

            self::assertValidDescription($fixerName, 'description', $definition->getDescription());
        }

        $samples = $definition->getCodeSamples();
        self::assertNotEmpty($samples, sprintf('[%s] Code samples are required.', $fixerName));

        $configSamplesProvided = [];
        $dummyFileInfo = new StdinFileInfo();

        foreach ($samples as $sampleCounter => $sample) {
            self::assertInstanceOf(
                CodeSampleInterface::class,
                $sample,
                sprintf('[%s] Sample #%d', $fixerName, $sampleCounter),
            );
            self::assertIsInt($sampleCounter);

            $code = $sample->getCode();

            self::assertNotEmpty($code, sprintf('[%s] Sample #%d', $fixerName, $sampleCounter));

            self::assertStringStartsNotWith(
                "\n",
                $code,
                sprintf('[%s] Sample #%d must not start with linebreak', $fixerName, $sampleCounter),
            );

            if (!$this->fixer instanceof SingleBlankLineAtEofFixer) {
                self::assertStringEndsWith(
                    "\n",
                    $code,
                    sprintf('[%s] Sample #%d must end with linebreak', $fixerName, $sampleCounter),
                );
            }

            $config = $sample->getConfiguration();

            if (null !== $config) {
                self::assertTrue(
                    $fixerIsConfigurable,
                    sprintf(
                        '[%s] Sample #%d has configuration, but the fixer is not configurable.',
                        $fixerName,
                        $sampleCounter,
                    ),
                );

                $configSamplesProvided[$sampleCounter] = $config;
            } elseif ($fixerIsConfigurable) {
                if (!$sample instanceof VersionSpecificCodeSampleInterface) {
                    self::assertArrayNotHasKey(
                        'default',
                        $configSamplesProvided,
                        sprintf('[%s] Multiple non-versioned samples with default configuration.', $fixerName),
                    );
                }

                $configSamplesProvided['default'] = true;
            }

            if ($sample instanceof VersionSpecificCodeSampleInterface) {
                $supportedPhpVersions = [7_04_00, 8_00_00, 8_01_00, 8_02_00, 8_03_00, 8_04_00];

                $hasSuitableSupportedVersion = false;
                foreach ($supportedPhpVersions as $version) {
                    if ($sample->isSuitableFor($version)) {
                        $hasSuitableSupportedVersion = true;
                    }
                }
                self::assertTrue(
                    $hasSuitableSupportedVersion,
                    'Version specific code sample must be suitable for at least 1 supported PHP version.',
                );

                $hasUnsuitableSupportedVersion = false;
                foreach ($supportedPhpVersions as $version) {
                    if (!$sample->isSuitableFor($version)) {
                        $hasUnsuitableSupportedVersion = true;
                    }
                }
                self::assertTrue(
                    $hasUnsuitableSupportedVersion,
                    'Version specific code sample must be unsuitable for at least 1 supported PHP version.',
                );

                if (!$sample->isSuitableFor(PHP_VERSION_ID)) {
                    continue;
                }
            }

            if ($this->fixer instanceof ConfigurableFixerInterface) {
                // always re-configure as the fixer might have been configured with diff. configuration form previous sample
                $this->fixer->configure($config ?? []);
            }

            Tokens::clearCache();
            $tokens = Tokens::fromCode($code);
            $this->fixer->fix(
                $sample instanceof FileSpecificCodeSampleInterface ? $sample->getSplFileInfo() : $dummyFileInfo,
                $tokens,
            );

            self::assertTrue(
                $tokens->isChanged(),
                sprintf('[%s] Sample #%d is not changed during fixing.', $fixerName, $sampleCounter),
            );

            $duplicatedCodeSample = array_search(
                $sample,
                array_slice($samples, 0, $sampleCounter),
                true,
            );

            self::assertFalse(
                $duplicatedCodeSample,
                sprintf('[%s] Sample #%d duplicates #%d.', $fixerName, $sampleCounter, $duplicatedCodeSample),
            );
        }

        if ($this->fixer instanceof ConfigurableFixerInterface) {
            if (isset($configSamplesProvided['default'])) {
                self::assertSame(
                    'default',
                    array_key_first($configSamplesProvided),
                    sprintf('[%s] First sample must be for the default configuration.', $fixerName),
                );
            } elseif (!isset($this->allowedFixersWithoutDefaultCodeSample[$fixerName])) {
                self::assertArrayHasKey(
                    $fixerName,
                    $this->allowedRequiredOptions,
                    sprintf('[%s] Has no sample for default configuration.', $fixerName),
                );
            }

            if (count($configSamplesProvided) < 2) {
                self::fail(
                    sprintf(
                        '[%s] Configurable fixer only provides a default configuration sample and none for its configuration options.',
                        $fixerName,
                    ),
                );
            }

            $options = $this->fixer->getConfigurationDefinition()
                ->getOptions()
            ;

            foreach ($options as $option) {
                self::assertMatchesRegularExpression(
                    '/^[a-z_]+[a-z]$/',
                    $option->getName(),
                    sprintf('[%s] Option %s is not snake_case.', $fixerName, $option->getName()),
                );
                self::assertMatchesRegularExpression(
                    '/^[A-Z].+\.$/s',
                    $option->getDescription(),
                    sprintf(
                        '[%s] Description of option "%s" must start with capital letter and end with dot.',
                        $fixerName,
                        $option->getName(),
                    ),
                );
            }
        }

        self::assertIsInt($this->fixer->getPriority());
    }

    final public function testFixersAreFinal()
    {
        $reflection = new ReflectionClass($this->fixer);

        static::assertTrue(
            $reflection->isFinal(),
            sprintf('Fixer "%s" must be declared "final".', $this->fixer->getName()),
        );
    }

    final public function testFixersAreDefined()
    {
        static::assertInstanceOf(FixerInterface::class, $this->fixer);
    }

    final public function testDeprecatedFixersHaveCorrectSummary()
    {
        $reflection = new ReflectionClass($this->fixer);
        $comment = $reflection->getDocComment();

        static::assertStringNotContainsString(
            'DEPRECATED',
            $this->fixer->getDefinition()
                ->getSummary(),
            'Fixer cannot contain word "DEPRECATED" in summary',
        );

        if ($this->fixer instanceof DeprecatedFixerInterface) {
            static::assertStringContainsString('@deprecated', $comment);
        } elseif (is_string($comment)) {
            static::assertStringNotContainsString('@deprecated', $comment);
        }
    }

    /**
     * Blur filter that find candidate fixer for performance optimization to use only `insertSlices` instead of multiple `insertAt` if there is no other collection manipulation.
     */
    public function testFixerUseInsertSlicesWhenOnlyInsertionsArePerformed()
    {
        $reflection = new ReflectionClass($this->fixer);
        $tokens = Tokens::fromCode(file_get_contents($reflection->getFileName()));

        $sequences = $this->findAllTokenSequences($tokens, [[T_VARIABLE, '$tokens'], [T_OBJECT_OPERATOR], [T_STRING]]);

        $usedMethods = array_unique(array_map(function (array $sequence) {
            $last = end($sequence);

            return $last->getContent();
        }, $sequences));

        // if there is no `insertAt`, it's not a candidate
        if (!in_array('insertAt', $usedMethods, true)) {
            $this->addToAssertionCount(1);

            return;
        }

        $usedMethods = array_filter($usedMethods, function ($method) {
            return false === Preg::match('/^(count|find|generate|get|is|rewind)/', $method);
        });

        $allowedMethods = ['insertAt'];
        $nonAllowedMethods = array_diff($usedMethods, $allowedMethods);

        if ([] === $nonAllowedMethods) {
            $fixerName = $this->fixer->getName();
            if (in_array($fixerName, [
                // DO NOT add anything to this list at ease, align with core contributors whether it makes sense to insert tokens individually or by bulk for your case.
                // The original list of the fixers being exceptions and insert tokens individually came from legacy reasons when it was the only available methods to insert tokens.
                'blank_line_after_namespace',
                'blank_line_after_opening_tag',
                'blank_line_before_statement',
                'class_attributes_separation',
                'date_time_immutable',
                'declare_strict_types',
                'doctrine_annotation_braces',
                'doctrine_annotation_spaces',
                'final_internal_class',
                'final_public_method_for_abstract_class',
                'function_typehint_space',
                'heredoc_indentation',
                'method_chaining_indentation',
                'native_constant_invocation',
                'new_with_braces',
                'no_short_echo_tag',
                'not_operator_with_space',
                'not_operator_with_successor_space',
                'php_unit_internal_class',
                'php_unit_no_expectation_annotation',
                'php_unit_set_up_tear_down_visibility',
                'php_unit_size_class',
                'php_unit_test_annotation',
                'php_unit_test_class_requires_covers',
                'phpdoc_to_param_type',
                'phpdoc_to_property_type',
                'phpdoc_to_return_type',
                'random_api_migration',
                'semicolon_after_instruction',
                'single_line_after_imports',
                'static_lambda',
                'strict_param',
                'void_return',
            ], true)) {
                static::markTestIncomplete(
                    sprintf(
                        'Fixer "%s" may be optimized to use `Tokens::insertSlices` instead of `%s`, please help and optimize it.',
                        $fixerName,
                        implode(', ', $allowedMethods),
                    ),
                );
            }
            static::fail(
                sprintf(
                    'Fixer "%s" shall be optimized to use `Tokens::insertSlices` instead of `%s`.',
                    $fixerName,
                    implode(', ', $allowedMethods),
                ),
            );
        }

        $this->addToAssertionCount(1);
    }

    final public function testFixerConfigurationDefinitions()
    {
        if (!$this->fixer instanceof ConfigurableFixerInterface) {
            $this->addToAssertionCount(1); // not applied to the fixer without configuration

            return;
        }

        if ($this->fixer->getName() == 'my_test_fixer') {
            $this->addToAssertionCount(1); // not applied to the my_test_fixer
            return;
        }

        $configurationDefinition = $this->fixer->getConfigurationDefinition();

        static::assertInstanceOf(FixerConfigurationResolverInterface::class, $configurationDefinition);

        foreach ($configurationDefinition->getOptions() as $option) {
            static::assertInstanceOf(FixerOptionInterface::class, $option);
            static::assertNotEmpty($option->getDescription());

            static::assertSame(
                !isset($this->allowedRequiredOptions[$this->fixer->getName()][$option->getName()]),
                $option->hasDefault(),
                sprintf(
                    $option->hasDefault()
                        ? 'Option `%s` of fixer `%s` is wrongly listed in `$allowedRequiredOptions` structure, as it is not required. If you just changed that option to not be required anymore, please adjust mentioned structure.'
                        : 'Option `%s` of fixer `%s` shall not be required. If you want to introduce new required option please adjust `$allowedRequiredOptions` structure.',
                    $option->getName(),
                    $this->fixer->getName(),
                ),
            );

            static::assertStringNotContainsString(
                'DEPRECATED',
                $option->getDescription(),
                'Option description cannot contain word "DEPRECATED"',
            );
        }
    }

    final public function testFixersReturnTypes()
    {
        $tokens = Tokens::fromCode('<?php ');
        $emptyTokens = new Tokens();

        static::assertIsInt(
            $this->fixer->getPriority(),
            sprintf('Return type for ::getPriority of "%s" is invalid.', $this->fixer->getName()),
        );
        static::assertIsBool(
            $this->fixer->supports(new SplFileInfo(__FILE__)),
            sprintf('Return type for ::supports of "%s" is invalid.', $this->fixer->getName()),
        );

        static::assertIsBool(
            $this->fixer->isCandidate($emptyTokens),
            sprintf('Return type for ::isCandidate with empty tokens of "%s" is invalid.', $this->fixer->getName()),
        );
        static::assertFalse($emptyTokens->isChanged());

        static::assertIsBool(
            $this->fixer->isCandidate($tokens),
            sprintf('Return type for ::isCandidate of "%s" is invalid.', $this->fixer->getName()),
        );
        static::assertFalse($tokens->isChanged());

        if ($this->fixer instanceof HeaderCommentFixer) {
            $this->fixer->configure(['header' => 'a']);
        }

        static::assertNull(
            $this->fixer->fix(new SplFileInfo(__FILE__), $emptyTokens),
            sprintf('Return type for ::fix with empty tokens of "%s" is invalid.', $this->fixer->getName()),
        );
        static::assertFalse($emptyTokens->isChanged());

        static::assertNull(
            $this->fixer->fix(new SplFileInfo(__FILE__), $tokens),
            sprintf('Return type for ::fix of "%s" is invalid.', $this->fixer->getName()),
        );
    }

    protected function getFixer(): FixerInterface
    {
        assert($this->fixer instanceof FixerInterface);

        if ($this->fixer instanceof WhitespacesAwareFixerInterface) {
            $this->fixer->setWhitespacesConfig(new WhitespacesFixerConfig());
        }

        return $this->fixer;
    }

    final protected function doTest(string $expected, ?string $input = null, ?SplFileInfo $file = null): void
    {
        if ($expected === $input) {
            throw new InvalidArgumentException('Input parameter must not be equal to expected parameter.');
        }

        $file ??= new SplFileInfo(__FILE__);
        $fileIsSupported = $this->fixer->supports($file);

        if (null !== $input) {
            self::assertNull($this->lintSource($input));

            Tokens::clearCache();
            $tokens = Tokens::fromCode($input);

            if ($fileIsSupported) {
                self::assertTrue($this->fixer->isCandidate($tokens), 'Fixer must be a candidate for input code.');
                self::assertFalse($tokens->isChanged(), 'Fixer must not touch Tokens on candidate check.');
                $this->fixer->fix($file, $tokens);
            }

            $this->assertSame($tokens->generateCode(), $expected);

            self::assertTrue(
                $tokens->isChanged(),
                'Tokens collection built on input code must be marked as changed after fixing.',
            );

            $tokens->clearEmptyTokens();

            self::assertSameSize(
                $tokens,
                array_unique(array_map(static fn (Token $token): string => spl_object_hash($token), $tokens->toArray()),
                ),
                'Token items inside Tokens collection must be unique.',
            );

            Tokens::clearCache();
            $expectedTokens = Tokens::fromCode($expected);
            $this->assertSameTokens($expectedTokens, $tokens);
        }

        self::assertNull($this->lintSource($expected));

        Tokens::clearCache();
        $tokens = Tokens::fromCode($expected);

        if ($fileIsSupported) {
            $this->fixer->fix($file, $tokens);
        }

        $this->assertSame($tokens->generateCode(), $expected);
        self::assertFalse(
            $tokens->isChanged(),
            'Tokens collection built on expected code must not be marked as changed after fixing.',
        );
    }

    protected function lintSource(string $source): ?string
    {
        try {
            $this->linter->lintSource($source)
                ->check()
            ;
        } catch (Exception $e) {
            return $e->getMessage() . "\n\nSource:\n{$source}";
        }

        return null;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->linter = $this->getLinter();
        $this->fixer = $this->createFixer();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->linter = null;
        $this->fixer = null;
    }

    private function findAllTokenSequences($tokens, $sequence): array
    {
        $lastIndex = 0;
        $sequences = [];
        while ($found = $tokens->findSequence($sequence, $lastIndex)) {
            $keys = array_keys($found);
            $sequences[] = $found;
            $lastIndex = $keys[2];
        }

        return $sequences;
    }

    private static function assertCorrectCasing($needle, $haystack, $message)
    {
        static::assertSame(
            substr_count(strtolower($haystack), strtolower($needle)),
            substr_count($haystack, $needle),
            $message,
        );
    }

    private function getLinter(): LinterInterface
    {
        static $linter = null;

        if (null === $linter) {
            $linter = new CachingLinter(
                getenv('FAST_LINT_TEST_CASES') ? new Linter() : new ProcessLinter(),
            );
        }

        return $linter;
    }

    private static function assertValidDescription($fixerName, $descriptionType, $description)
    {
        static::assertIsString($description);
        static::assertMatchesRegularExpression(
            '/^[A-Z`][^"]+\.$/',
            $description,
            sprintf('[%s] The %s must start with capital letter or a ` and end with dot.', $fixerName, $descriptionType),
        );
        static::assertStringNotContainsString(
            'phpdocs',
            $description,
            sprintf('[%s] `PHPDoc` must not be in the plural in %s.', $fixerName, $descriptionType),
        );
        static::assertCorrectCasing(
            $description,
            'PHPDoc',
            sprintf('[%s] `PHPDoc` must be in correct casing in %s.', $fixerName, $descriptionType),
        );
        static::assertCorrectCasing(
            $description,
            'PHPUnit',
            sprintf('[%s] `PHPUnit` must be in correct casing in %s.', $fixerName, $descriptionType),
        );
        static::assertFalse(
            strpos($descriptionType, '``'),
            sprintf('[%s] The %s must no contain sequential backticks.', $fixerName, $descriptionType),
        );
    }

    private function assertSameTokens(Tokens $expectedTokens, Tokens $inputTokens): void
    {
        $this->assertCount($expectedTokens->count(), $inputTokens, 'Both collections must have the same size.');

        foreach ($expectedTokens as $index => $expectedToken) {
            $inputToken = $inputTokens[$index];

            $this->assertTrue(
                $expectedToken->equals($inputToken),
                sprintf(
                    "Token at index %d must be:\n%s,\ngot:\n%s.",
                    $index,
                    $expectedToken->toJson(),
                    $inputToken->toJson(),
                ),
            );
        }
    }
}
