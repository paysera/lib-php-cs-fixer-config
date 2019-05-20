<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ClassNamingFixer extends AbstractFixer
{
    const CONVENTION = 'PhpBasic convention 2.5.2: For services suffix has to represent the job of that service';
    const SERVICE = 'Service';

    private $validServiceSuffixes;
    private $invalidSuffixes;

    public function __construct()
    {
        parent::__construct();
        $this->validServiceSuffixes = [
            'Registry',
            'Factory',
            'Client',
            'Plugin',
            'Proxy',
            'Interface',
            'Repository',
        ];
        $this->invalidSuffixes = [
            'Service',
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We use nouns for class names.
            For services we use some suffix to represent the job of that service, usually *er:
            manager
            normalizer
            provider
            updater
            controller
            registry
            resolver
            We do not use service as a suffix, as this does not represent anything (for example PageService).
            We use object names only for entities, not for services (for example Page).
            ',
            [
                new CodeSample('
                <?php
                    class SampleService
                    {
                    
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_class_naming';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_CLASS);
    }

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        if ($this->configuration['service_suffixes'] === true) {
            return;
        }
        if (isset($this->configuration['service_suffixes']['valid'])) {
            $this->validServiceSuffixes = $this->configuration['service_suffixes']['valid'];
        }
        if (isset($this->configuration['service_suffixes']['invalid'])) {
            $this->invalidSuffixes = $this->configuration['service_suffixes']['invalid'];
        }
    }

    protected function createConfigurationDefinition()
    {
        $suffixes = new FixerOptionBuilder(
            'service_suffixes',
            'Set valid and invalid suffixes for Class names.'
        );

        $suffixes = $suffixes
            ->setAllowedTypes(['array', 'bool'])
            ->getOption()
        ;

        return new FixerConfigurationResolverRootless('service_suffixes', [$suffixes], $this->getName());
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $classNamespace = null;
        $valid = true;
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $semicolonIndex = $tokens->getNextTokenOfKind($key, [';']);
                if ($tokens[$semicolonIndex - 1]->isGivenKind(T_STRING)) {
                    $classNamespace = $tokens[$semicolonIndex - 1]->getContent();
                }
            }
            if (!$token->isGivenKind(T_CLASS)) {
                continue;
            }

            $classNameIndex = $tokens->getNextMeaningfulToken($key);
            if (!$tokens[$classNameIndex]->isGivenKind(T_STRING)) {
                continue;
            }

            $previousTokenIndex = $tokens->getPrevMeaningfulToken($key);
            if (strpos($tokens[$key - 1]->getContent(), "\n") !== false) {
                $newLineIndex = $key - 1;
            } elseif (
                $tokens[$previousTokenIndex]->isGivenKind([T_ABSTRACT, T_FINAL])
                && strpos($tokens[$previousTokenIndex - 1]->getContent(), "\n") !== false
            ) {
                $newLineIndex = $previousTokenIndex - 1;
            }

            $className = $tokens[$classNameIndex]->getContent();
            if ($classNamespace !== null) {
                $valid = $this->isClassNameValid($className, $classNamespace);
            }

            if (!$valid && isset($newLineIndex)) {
                $this->insertComment($tokens, $newLineIndex, $className);
            }
        }
    }

    /**
     * @param int $className
     * @param string $classNamespace
     * @return bool
     */
    private function isClassNameValid($className, $classNamespace)
    {
        if ($classNamespace === self::SERVICE) {
            if (preg_match('#\w+(er\b|or\b)#', $className)) {
                return true;
            }
            foreach ($this->validServiceSuffixes as $validServiceSuffix) {
                if (preg_match('#' . $validServiceSuffix . '\b#', $className)) {
                    return true;
                }
            }
            foreach ($this->invalidSuffixes as $invalidSuffix) {
                if (preg_match('#' . $invalidSuffix . '\b#', $className)) {
                    return false;
                }
            }
            return false;
        }
        return true;
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param string $className
     */
    private function insertComment(Tokens $tokens, $insertIndex, $className)
    {
        $comment = '// TODO: "' . $className . '" - ' . self::CONVENTION;
        if (!$tokens[$tokens->getPrevNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt($insertIndex + 1, new Token([T_WHITESPACE, $tokens[$insertIndex]->getContent()]));
            $tokens->insertAt($insertIndex + 1, new Token([T_COMMENT, $comment]));
        }
    }
}
