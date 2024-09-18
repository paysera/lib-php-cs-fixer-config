<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer;

use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;

final class IgnorableFixerDecorator implements
    ConfigurableFixerInterface,
    WhitespacesAwareFixerInterface
{
    use ConfigurableFixerTrait {
        configure as public configureConfigurableFixerTrait;
    }

    public const IGNORE_ANNOTATION = '@php-cs-fixer-ignore';

    private FixerInterface $innerFixer;

    public function __construct(FixerInterface $innerFixer)
    {
        $this->innerFixer = $innerFixer;
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return $this->innerFixer->getDefinition();
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $this->innerFixer->isCandidate($tokens);
    }

    public function isRisky(): bool
    {
        return $this->innerFixer->isRisky();
    }

    public function getName(): string
    {
        return $this->innerFixer->getName();
    }

    public function getPriority(): int
    {
        return $this->innerFixer->getPriority();
    }

    public function supports(SplFileInfo $file): bool
    {
        return $this->innerFixer->supports($file);
    }

    public function fix(SplFileInfo $file, Tokens $tokens): void
    {
        $ignoreNotice = self::IGNORE_ANNOTATION . ' ' . $this->getName();
        $contents = $tokens->generateCode();
        if (str_contains($contents, $ignoreNotice)) {
            return;
        }

        $this->innerFixer->fix($file, $tokens);
    }

    public function configure(array $configuration = null): void
    {
        if (!$this->innerFixer instanceof ConfigurableFixerInterface) {
            throw new InvalidFixerConfigurationException($this->getName(), 'Is not configurable.');
        }

        $this->innerFixer->configure($configuration);
    }

    public function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([]);
    }

    public function setWhitespacesConfig(WhitespacesFixerConfig $config): void
    {
        if ($this->innerFixer instanceof WhitespacesAwareFixerInterface) {
            $this->innerFixer->setWhitespacesConfig($config);
        }
    }
}
