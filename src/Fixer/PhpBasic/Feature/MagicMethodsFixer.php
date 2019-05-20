<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class MagicMethodsFixer extends AbstractFixer
{
    const TO_STRING = '__toString';
    const MAGIC_METHODS_CONVENTION = 'PhpBasic convention 3.14.1: We avoid magic methods';
    const STRING_CONVENTION = 'PhpBasic convention 3.12: We do not use __toString method for main functionality';

    private $magicMethods;

    public function __construct()
    {
        parent::__construct();
        $this->magicMethods = [
            self::TO_STRING,
            '__destruct',
            '__call',
            '__callStatic',
            '__get',
            '__set',
            '__isset',
            '__unset',
            '__sleep',
            '__wakeup',
            '__invoke',
            '__set_state',
            '__clone',
            '__debugInfo',
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We do not use __toString method for main functionality, only for debugging purposes.
            It applies to all magic methods except __construct().
            ',
            [
                new CodeSample('
                <?php
                class Sample
                {
                    private $foo;
                    
                    public function __toString()
                    {
                        return $this->foo;
                    }
                    
                    public function __clone()
                    {
                        return $this->foo;
                    }
                    
                    public function __call()
                    {
                        return $this->foo;
                    }
                }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_magic_methods';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            $methodName = $token->getContent();
            if ($token->isGivenKind(T_STRING) && in_array($methodName, $this->magicMethods, true)) {
                $parenthesesStartIndex = $tokens->getNextTokenOfKind($key, ['(']);
                $parenthesesEndIndex = $tokens->findBlockEnd(
                    Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                    $parenthesesStartIndex
                );

                if ($tokens[$parenthesesEndIndex + 1]->equals(';')) {
                    $parenthesesEndIndex++;
                }

                if (!$tokens[$tokens->getNextNonWhitespace($parenthesesEndIndex)]->isGivenKind(T_COMMENT)) {
                    if ($methodName === self::TO_STRING) {
                        $this->insertComment($tokens, $parenthesesEndIndex + 1, $methodName, self::STRING_CONVENTION);
                    } else {
                        $this->insertComment(
                            $tokens,
                            $parenthesesEndIndex + 1,
                            $methodName,
                            self::MAGIC_METHODS_CONVENTION
                        );
                    }
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param string $methodName
     * @param string $convention
     */
    private function insertComment(Tokens $tokens, $insertIndex, $methodName, $convention)
    {
        $tokens->insertAt($insertIndex, new Token([T_COMMENT, '// TODO: "' . $methodName . '" - ' . $convention]));
        $tokens->insertAt($insertIndex, new Token([T_WHITESPACE, ' ']));
    }
}
