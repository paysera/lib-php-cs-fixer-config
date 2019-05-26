<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer;

use PhpCsFixer\ConfigurationException\InvalidFixerConfigurationException;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\DefinedFixerInterface;
use PhpCsFixer\Fixer\FixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\WhitespacesFixerConfig;
use SplFileInfo;

class IgnorableFixerDecorator implements
    DefinedFixerInterface,
    ConfigurableFixerInterface,
    WhitespacesAwareFixerInterface
{
    const IGNORE_ANNOTATION = '@php-cs-fixer-ignore';

    private $innerFixer;

    public function __construct(FixerInterface $innerFixer)
    {
        $this->innerFixer = $innerFixer;
    }

    public function getDefinition()
    {
        if ($this->innerFixer instanceof DefinedFixerInterface) {
            return $this->innerFixer->getDefinition();
        }
        return new FixerDefinition('Description is not available.', []);

    }

    public function isCandidate(Tokens $tokens)
    {
        return $this->innerFixer->isCandidate($tokens);
    }

    public function isRisky()
    {
        return $this->innerFixer->isRisky();
    }

    public function getName()
    {
        return $this->innerFixer->getName();
    }

    public function getPriority()
    {
        return $this->innerFixer->getPriority();
    }

    public function supports(SplFileInfo $file)
    {
        return $this->innerFixer->supports($file);
    }

    public function fix(SplFileInfo $file, Tokens $tokens)
    {
        $ignoreNotice = self::IGNORE_ANNOTATION . ' ' . $this->getName();
        $contents = file_get_contents($file->getPathname());
        if (strpos($contents, $ignoreNotice) !== false) {
            return;
        }

        $this->innerFixer->fix($file, $tokens);
    }

    public function configure(array $configuration = null)
    {
        if (!$this->innerFixer instanceof ConfigurableFixerInterface) {
            throw new InvalidFixerConfigurationException($this->getName(), 'Is not configurable.');
        }

        $this->innerFixer->configure($configuration);
    }

    public function setWhitespacesConfig(WhitespacesFixerConfig $config)
    {
        if ($this->innerFixer instanceof WhitespacesAwareFixerInterface) {
            $this->innerFixer->setWhitespacesConfig($config);
        }
    }
}
