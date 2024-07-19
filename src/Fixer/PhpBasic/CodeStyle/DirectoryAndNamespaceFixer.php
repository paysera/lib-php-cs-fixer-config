<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Util\Inflector;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class DirectoryAndNamespaceFixer extends AbstractFixer
{
    public const DIRECTORY_INTERFACE = 'Interface';
    public const SINGULAR_CONVENTION = 'PhpBasic convention 2.7.1: We use singular for namespaces';
    public const INTERFACE_CONVENTION = 'PhpBasic convention 2.7.2: We do not make directories just for interfaces';
    public const ABSTRACTION_CONVENTION = 'PhpBasic convention 2.7.3: We use abstractions for namespaces';

    private array $exclusions;
    private array $serviceNames;
    private $inflector;

    public function __construct()
    {
        parent::__construct();

        $this->exclusions = [
            'Tests',
            'Data',
            'Sonata',
            'XLS',
        ];

        $this->serviceNames = [
            'Manager',
        ];
    }

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<TEXT
We use singular for namespaces: Service, Bundle, Entity, Controller etc.
Exception: if English word does not have singular form.

We do not make directories just for interfaces.
We put them together with services by related functionality (no ServiceInterface namespace).

We use abstractions for namespaces, not service names.
For example, UserMerge or UserMerging, not UserMergeManager.
TEXT
            ,
            [
                new CodeSample(
                    <<<'PHP'
<?php
namespace Some\Invalid\Namespaces\Namings;

namespace Evp\Bundle\UserBundle\ServiceInterface\MergeProviderInterface;

namespace Evp\Bundle\UserBundle\UserManager;

PHP,
                ),
            ],
            null,
            'Paysera recommendation.'
        );
    }

    public function getPriority(): int
    {
        // Should run after `BlankLineAfterNamespaceFixer`
        return -25;
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_code_style_directory_and_namespace';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_NAMESPACE);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $this->validateNamespace($tokens, $key);
            }
        }
    }

    private function validateNamespace(Tokens $tokens, int $index)
    {
        $semicolonIndex = $tokens->getNextTokenOfKind($index, [';']);

        for ($i = $index; $i < $semicolonIndex; $i++) {
            if ($tokens[$index]->isGivenKind(T_STRING)) {
                $namespaceName = $tokens[$index]->getContent();
                if (
                    $namespaceName !== (new Inflector())->singularize($namespaceName)
                    && !in_array($namespaceName, $this->exclusions, true)
                ) {
                    $this->insertComment($tokens, $semicolonIndex, $namespaceName, self::SINGULAR_CONVENTION);
                    break;
                }

                if (strpos($namespaceName, self::DIRECTORY_INTERFACE)) {
                    $this->insertComment($tokens, $semicolonIndex, $namespaceName, self::INTERFACE_CONVENTION);
                    break;
                }

                foreach ($this->serviceNames as $serviceName) {
                    if (preg_match('#[\w]+' . $serviceName . '\b#', $namespaceName)) {
                        $this->insertComment($tokens, $semicolonIndex, $namespaceName, self::ABSTRACTION_CONVENTION);
                        break;
                    }
                }
            }
            $index++;
        }
    }

    private function insertComment(Tokens $tokens, int $insertIndex, string $namespaceName, string $convention)
    {
        $comment = '// TODO: "' . $namespaceName . '" - ' . $convention;
        $commentIndex = $tokens->getNextNonWhitespace($insertIndex);
        if ($commentIndex === null || !$tokens[$commentIndex]->isGivenKind(T_COMMENT)) {
            $tokens->insertSlices([
                $insertIndex + 1 => [
                    new Token([T_WHITESPACE, ' ']),
                    new Token([T_COMMENT, $comment]),
                ]
            ]);
        }
    }
}
