<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class InterfaceNamingFixer extends AbstractFixer
{
    const INTERFACE_NAME = 'Interface';

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We always add suffix Interface to interfaces, even if interface name would be adjective.
            Risky for renaming interface name.
            ',
            [
                new CodeSample('
                <?php
                    interface Sample
                    {
                        
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_interface_naming';
    }

    public function isRisky()
    {
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_INTERFACE);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_INTERFACE)) {
                continue;
            }

            $interfaceNameIndex = $tokens->getNextMeaningfulToken($key);
            if (!$tokens[$interfaceNameIndex]->isGivenKind(T_STRING)) {
                continue;
            }

            $this->validateInterfaceName($tokens, $interfaceNameIndex);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $interfaceNameIndex
     */
    private function validateInterfaceName(Tokens $tokens, $interfaceNameIndex)
    {
        $interfaceName = $tokens[$interfaceNameIndex]->getContent();
        if (!preg_match('#(' . self::INTERFACE_NAME . ')#', $interfaceName)) {
            $tokens[$interfaceNameIndex]->setContent($tokens[$interfaceNameIndex]->getContent() . self::INTERFACE_NAME);
        }
    }
}
