<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use Doctrine\Common\Inflector\Inflector;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class DirectoryAndNamespaceFixer extends AbstractFixer
{
    const DIRECTORY_INTERFACE = 'Interface';
    const SINGULAR_CONVENTION = 'PhpBasic convention 2.7.1: We use singular for namespaces';
    const INTERFACE_CONVENTION = 'PhpBasic convention 2.7.2: We do not make directories just for interfaces';
    const ABSTRACTION_CONVENTION = 'PhpBasic convention 2.7.3: We use abstractions for namespaces';

    private $exclusions;
    private $serviceNames;

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

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We use singular for namespaces: Service, Bundle, Entity, Controller etc.
            Exception: if English word does not have singular form.
            
            We do not make directories just for interfaces.
            We put them together with services by related functionality (no ServiceInterface namespace).
            
            We use abstractions for namespaces, not service names.
            For example, UserMerge or UserMerging, not UserMergeManager.
            ',
            [
                new CodeSample('
                <?php
                namespace Some\Invalid\Namespaces\Namings;
                
                namespace Evp\Bundle\UserBundle\ServiceInterface\MergeProviderInterface;
                
                namespace Evp\Bundle\UserBundle\UserManager;
                '),
            ]
        );
    }

    public function getPriority()
    {
        // Should run after `BlankLineAfterNamespaceFixer`
        return -25;
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_directory_and_namespace';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_NAMESPACE);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $this->validateNamespace($tokens, $key);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $index
     */
    private function validateNamespace(Tokens $tokens, $index)
    {
        $semicolonIndex = $tokens->getNextTokenOfKind($index, [';']);

        for ($i = $index; $i < $semicolonIndex; $i++) {
            if ($tokens[$index]->isGivenKind(T_STRING)) {
                $namespaceName = $tokens[$index]->getContent();
                if (
                    $namespaceName !== Inflector::singularize($namespaceName)
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

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param string $namespaceName
     * @param string $convention
     */
    private function insertComment(Tokens $tokens, $insertIndex, $namespaceName, $convention)
    {
        $comment = '// TODO: "' . $namespaceName . '" - ' . $convention;
        $commentIndex = $tokens->getNextNonWhitespace($insertIndex);
        if ($commentIndex === null || !$tokens[$commentIndex]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt($insertIndex + 1, new Token([T_COMMENT, $comment]));
            $tokens->insertAt($insertIndex + 1, new Token([T_WHITESPACE, ' ']));
        }
    }
}
