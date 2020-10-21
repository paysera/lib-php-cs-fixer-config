<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class DefaultValuesInConstructorFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    const CONSTRUCT = '__construct';

    public function getDefinition()
    {
        return new FixerDefinition(
            'We declare default variables in constructor',
            [
                new CodeSample(
                    '<?php
                    
                    class Sample
                    {
                        private $array;
                        private $integer;
                        
                        public function __construct()
                        {
                            $this->array = [];
                            $this->integer = 1;
                        }
                    }'
                ),
            ]
        );
    }

    public function isRisky()
    {
        return true;
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_default_values_in_constructor';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_CLASS]);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $parentConstructNeeded = false;
        $isConstructorPresent = false;
        $propertiesWithDefaultValues = [];
        $endOfPropertyDeclarationSemicolon = 0;

        foreach ($tokens as $key => $token) {
            if ($this->isConstructor($key, $tokens, $token)) {
                $isConstructorPresent = true;
            }
        }

        $classFound = false;
        foreach ($tokens as $key => $token) {
            if (!$classFound) {
                $classFound = $token->isGivenKind([T_CLASS]);
                continue;
            }

            $extends = $token->isGivenKind([T_EXTENDS]);
            if ($extends !== false) {
                $parentConstructNeeded = true;
            }
            $subsequentDeclarativeToken = $tokens->getNextMeaningfulToken($key);
            if (
                $token->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                && !$tokens[$subsequentDeclarativeToken]->isGivenKind(T_STATIC)
                && !$tokens[$subsequentDeclarativeToken]->isGivenKind(T_FUNCTION)
            ) {
                $propertyNameIndex = $tokens->getNextNonWhitespace($key);
                $endOfPropertyDeclarationSemicolon = $tokens->getNextTokenOfKind($key, [';']);

                if ($tokens[$propertyNameIndex]->isGivenKind(T_CONST)) {
                    continue;
                }

                if ($tokens[$propertyNameIndex]->isGivenKind(10022)) {
                    $propertyNameIndex = $tokens->getNextNonWhitespace($propertyNameIndex);
                }

                if (!$tokens[$propertyNameIndex]->isGivenKind(T_VARIABLE)) {
                    $propertyNameIndex = $tokens->getNextNonWhitespace($propertyNameIndex);
                }

                if ($tokens[$tokens->getNextMeaningfulToken($propertyNameIndex)]->equals(';')) {
                    continue;
                }

                for ($i = $propertyNameIndex + 1; $i < $endOfPropertyDeclarationSemicolon; $i++) {
                    $propertiesWithDefaultValues[$tokens[$propertyNameIndex]->getContent()][] = $tokens[$i];
                }
                $tokens->clearRange($propertyNameIndex + 1, $endOfPropertyDeclarationSemicolon - 1);
            }

            if ($this->isConstructor($key, $tokens, $token)) {
                $curlyBracesStartIndex = $tokens->getNextTokenOfKind($key, ['{']);
                $indentation = $tokens[$curlyBracesStartIndex + 1]->getContent();
                $curlyBracesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBracesStartIndex);

                $previousMeaningfulToken = $tokens->getPrevMeaningfulToken($curlyBracesEndIndex);

                if ($tokens[$previousMeaningfulToken]->equals('{')) {
                    $indentation .= $this->whitespacesConfig->getIndent();
                }

                $this->insertDefaultPropertiesInConstruct(
                    $tokens,
                    $previousMeaningfulToken,
                    $indentation,
                    $propertiesWithDefaultValues
                );
            }

            if (
                $this->isEndOfPropertiesDeclaration($key, $tokens, $token)
                && !$isConstructorPresent
                && count($propertiesWithDefaultValues) > 0
            ) {
                $endOfDeclarationNewLine = $tokens[$endOfPropertyDeclarationSemicolon + 1];

                $closingCurlyBrace = $tokens->getNextTokenOfKind($key, ['}']);
                $indentation = $tokens[$closingCurlyBrace - 1]->getContent();
                $index = $this->insertConstructTokensAndReturnOpeningBraceIndex(
                    $tokens,
                    $endOfPropertyDeclarationSemicolon + 1,
                    $indentation
                );
                $tokens->insertAt($index + 3, new Token([T_WHITESPACE, $endOfDeclarationNewLine->getContent()]));

                if ($tokens[$index]->equals('{')) {
                    $indentation .= $this->whitespacesConfig->getIndent();
                }
                if ($parentConstructNeeded) {
                    $index = $this->insertParentConstructAndReturnIndex($tokens, $index, $indentation);
                }

                $this->insertDefaultPropertiesInConstruct($tokens, $index, $indentation, $propertiesWithDefaultValues);

                return;
            }
        }
    }

    private function insertDefaultPropertiesInConstruct(
        Tokens $tokens,
        int $index,
        string $indentation,
        array $propertiesWithDefaultValues
    ) {
        foreach ($propertiesWithDefaultValues as $name => $propertyTokens) {
            $tokens->insertAt(++$index, new Token([T_WHITESPACE, $indentation]));
            $tokens->insertAt(++$index, new Token([T_VARIABLE, '$this']));
            $tokens->insertAt(++$index, new Token([T_OBJECT_OPERATOR, '->']));
            $tokens->insertAt(++$index, new Token([T_STRING, str_replace('$', '', $name)]));

            /** @var Token $item */
            foreach ($propertyTokens as $item) {
                if (false !== strpos($item->getContent(), "\n")) {
                    $itemIndentation = $item->getContent() . $this->whitespacesConfig->getIndent();
                    $tokens->insertAt(++$index, new Token([T_WHITESPACE, $itemIndentation]));
                } else {
                    $tokens->insertAt(++$index, $item);
                }
            }
            $tokens->insertAt(++$index, new Token(';'));

            $tokens->getPrevMeaningfulToken($index + 1);
        }
    }

    private function isEndOfPropertiesDeclaration(int $key, Tokens $tokens, Token $token)
    {
        $nextMeaningfulToken = $tokens->getNextMeaningfulToken($key);
        $subsequentToken = $tokens->getNextNonWhitespace($nextMeaningfulToken);

        return (
                $token->equals(';')
                && $tokens[$nextMeaningfulToken]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                && !$tokens[$subsequentToken]->isGivenKind(T_VARIABLE)
            || (
                $token->equals(';')
                && !$tokens[$nextMeaningfulToken]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            )
        );
    }

    private function isConstructor(int $key, Tokens $tokens, Token $token)
    {
        $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
        return
            $tokens[$key]->isGivenKind(T_STRING)
            && $token->getContent() === self::CONSTRUCT
            && $tokens[$key + 1]->equals('(')
            && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
        ;
    }

    private function insertConstructTokensAndReturnOpeningBraceIndex(Tokens $tokens, int $index, string $indentation)
    {
        $tokens->insertAt(++$index, new Token([T_PUBLIC, 'public']));
        $tokens->insertAt(++$index, new Token([T_WHITESPACE, ' ']));
        $tokens->insertAt(++$index, new Token([T_FUNCTION, 'function']));
        $tokens->insertAt(++$index, new Token([T_WHITESPACE, ' ']));
        $tokens->insertAt(++$index, new Token([T_STRING, self::CONSTRUCT]));
        $tokens->insertAt(++$index, new Token('('));
        $tokens->insertAt(++$index, new Token(')'));
        $tokens->insertAt(++$index, new Token([T_WHITESPACE, $indentation]));
        $tokens->insertAt(++$index, new Token('{'));
        $openingCurlyBrace = $index;
        $tokens->insertAt(++$index, new Token([T_WHITESPACE, $indentation]));
        $tokens->insertAt(++$index, new Token('}'));

        return $openingCurlyBrace;
    }

    private function insertParentConstructAndReturnIndex(Tokens $tokens, int $index, string $indentation)
    {
        $tokens->insertAt(++$index, new Token([T_WHITESPACE, $indentation]));
        $tokens->insertAt(++$index, new Token([T_STRING, 'parent']));
        $tokens->insertAt(++$index, new Token([T_DOUBLE_COLON, '::']));
        $tokens->insertAt(++$index, new Token([T_STRING, self::CONSTRUCT]));
        $tokens->insertAt(++$index, new Token('('));
        $tokens->insertAt(++$index, new Token(')'));
        $tokens->insertAt(++$index, new Token(';'));

        return $index;
    }
}
