<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class MagicMethodsFixer extends AbstractFixer
{
    public const TO_STRING = '__toString';
    public const MAGIC_METHODS_CONVENTION = 'PhpBasic convention 3.14.1: We avoid magic methods';
    public const STRING_CONVENTION = 'PhpBasic convention 3.12: We do not use __toString method for main functionality';

    private array $magicMethods;

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

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<'TEXT'
We do not use __toString method for main functionality, only for debugging purposes.
It applies to all magic methods except __construct().
TEXT,
            [
                new CodeSample(
                    <<<'PHP'
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

PHP,
                ),
            ],
            null,
            'Paysera recommendation.',
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_feature_magic_methods';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $key => $token) {
            $methodName = $token->getContent();
            if ($token->isGivenKind(T_STRING) && in_array($methodName, $this->magicMethods, true)) {
                $parenthesesStartIndex = $tokens->getNextTokenOfKind($key, ['(']);
                $parenthesesEndIndex = $tokens->findBlockEnd(
                    Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                    $parenthesesStartIndex,
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
                            self::MAGIC_METHODS_CONVENTION,
                        );
                    }
                }
            }
        }
    }

    private function insertComment(Tokens $tokens, int $insertIndex, string $methodName, string $convention)
    {
        $tokens->insertSlices([
            $insertIndex => [
                new Token([T_WHITESPACE, ' ']),
                new Token([T_COMMENT, '// TODO: "' . $methodName . '" - ' . $convention]),
            ],
        ]);
    }
}
