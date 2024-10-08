<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class PhpDocOnPropertiesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    public const MISSING_DOC_BLOCK_CONVENTION = 'PhpBasic convention 4.3: Missing DocBlock';
    public const CONSTRUCT = '__construct';

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            <<<'TEXT'
We use PhpDoc on properties that are not injected via constructor.

We do NOT put PhpDoc on services, that are type-casted and injected via constructor,
as they are automatically recognised by IDE and desynchronization between typecast and
PhpDoc can cause warnings to be silenced.

We may add PhpDoc on properties that are injected via constructor and are scalar,
but this is not necessary as IDE gets the type from constructor’s PhpDoc.
TEXT,
            [
                new CodeSample(
                    <<<'PHP'
<?php
class Sample
{
    private $someVariable;
    
    public function someFunction()
    {
        $a = $this->someVariable;
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
        return 'Paysera/php_basic_comment_php_doc_on_properties';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isAnyTokenKindsFound([T_PUBLIC, T_PROTECTED, T_PRIVATE]);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $constructFunction = [];
        // Collecting __construct function info
        foreach ($tokens as $key => $token) {
            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $functionTokenIndex ? $tokens->getPrevNonWhitespace($functionTokenIndex) : null;
            if (
                $tokens[$key]->isGivenKind(T_STRING)
                && $token->getContent() === self::CONSTRUCT
                && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
            ) {
                $constructFunction['ConstructArguments'] = $this->getConstructArguments($tokens, $key + 1);
                $index = $tokens->getPrevNonWhitespace($visibilityTokenIndex);
                if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                    $constructFunction['DocBlock'] = $tokens[$index]->getContent();
                } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
                    $constructFunction['DocBlock'] = $tokens[$tokens->getPrevNonWhitespace($index)]->getContent();
                }
                $constructFunction['Assignments'] = $this->getConstructAssignments($tokens, $key);
                break;
            }
        }

        // Inserting warning or removing Property DocBlock according to __construct
        foreach ($tokens as $key => $token) {
            $property = $this->getProperty($tokens, $key);
            if ($property === null) {
                continue;
            }

            // Missing DocBlock
            if (isset($property['DocBlockInsertIndex'])) {
                if ($constructFunction === null) {
                    $commentInsertions[$property['Variable']] = $property['DocBlockInsertIndex'];
                    $this->insertComment($tokens, $property['DocBlockInsertIndex'], $property['Variable']);
                } elseif (
                    $constructFunction !== null
                    && !$this->canPropertyTypeBeGuessed($property, $constructFunction)
                ) {
                    $commentInsertions[$property['Variable']] = $property['DocBlockInsertIndex'];
                    $this->insertComment($tokens, $property['DocBlockInsertIndex'], $property['Variable']);
                }

                continue;
            }

            // Existing DocBlock
            if (
                isset($property['DocBlockIndex'])
                && !$this->hasCommentAdditionalData($tokens[$property['DocBlockIndex']])
                && $this->isPropertyTypeKnownExactly($property, $constructFunction)
                && $tokens[$property['DocBlockIndex'] - 1]->isWhitespace()
            ) {
                $tokens->clearRange($property['DocBlockIndex'] - 1, $property['DocBlockIndex']);
            }
        }
    }

    private function canPropertyTypeBeGuessed(array $property, array $constructFunction): bool
    {
        return (
            $this->isPropertyDefinedInDocBlock($property, $constructFunction)
            || $this->isPropertyAssignedFromArgument($property, $constructFunction)
            || $this->isPropertyAssignedInConstructor($property, $constructFunction)
        );
    }

    private function isPropertyTypeKnownExactly(array $property, array $constructFunction): bool
    {
        return (
            $this->isPropertyDefinedInDocBlock($property, $constructFunction)
            || $this->isPropertyAssignedFromArgument($property, $constructFunction)
            || $this->isPropertyInstantiatedInConstructor($property, $constructFunction)
        );
    }

    private function isPropertyDefinedInDocBlock(array $property, array $constructFunction): bool
    {
        return
            isset($constructFunction['DocBlock'])
            && isset($property['Variable'])
            && preg_match('#\\' . $property['Variable'] . '#', $constructFunction['DocBlock'])
        ;
    }

    private function isPropertyAssignedFromArgument(array $property, array $constructFunction): bool
    {
        return
            isset($constructFunction['Assignments'][$property['Variable']])
            && in_array(
                $constructFunction['Assignments'][$property['Variable']],
                array_keys($constructFunction['ConstructArguments']),
                true,
            )
        ;
    }

    private function isPropertyAssignedInConstructor(array $property, array $constructFunction): bool
    {
        return
            isset($constructFunction['Assignments'][$property['Variable']])
            && in_array($constructFunction['Assignments'][$property['Variable']], ['new', 'array'], true)
        ;
    }

    private function isPropertyInstantiatedInConstructor(array $property, array $constructFunction): bool
    {
        return
            isset($constructFunction['Assignments'][$property['Variable']])
            && in_array($constructFunction['Assignments'][$property['Variable']], ['new'], true)
        ;
    }

    private function getConstructAssignments(Tokens $tokens, int $constructIndex): array
    {
        $curlyBracesStartIndex = $tokens->getNextTokenOfKind($constructIndex, ['{']);
        $curlyBracesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBracesStartIndex);

        $assignments = [];
        for ($i = $curlyBracesStartIndex; $i < $curlyBracesEndIndex; $i++) {
            if (
                $tokens[$i]->isGivenKind(T_VARIABLE)
                && $tokens[$i]->getContent() === '$this'
                && $tokens[$i + 1]->isGivenKind(T_OBJECT_OPERATOR)
            ) {
                $property = '$' . $tokens[$i + 2]->getContent();
                $equalsIndex = $tokens->getNextNonWhitespace($i + 2);
                if ($tokens[$equalsIndex]->getContent() !== '=') {
                    continue;
                }

                $i = $tokens->getNextNonWhitespace($equalsIndex);
                if ($tokens[$i]->getContent() === 'new') {
                    $assignments[$property] = 'new';
                    continue;
                } elseif ($tokens[$i]->getContent() === '[' || $tokens[$i]->getContent() === 'array') {
                    $assignments[$property] = 'array';
                    continue;
                }

                while ($tokens[$i]->getContent() !== ';') {
                    $i++;
                }
                $value = $tokens[$tokens->getPrevMeaningfulToken($i)]->getContent();
                $assignments[$property] = $value;
            }
        }

        return $assignments;
    }

    private function getConstructArguments(Tokens $tokens, int $parenthesesStartIndex): array
    {
        $constructArguments = [];
        $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $parenthesesStartIndex);
        for ($i = $parenthesesStartIndex; $i < $parenthesesEndIndex; $i++) {
            $previousTokenIndex = $tokens->getPrevMeaningfulToken($i);
            if (
                $tokens[$i]->isGivenKind(T_VARIABLE)
                && (
                    $tokens[$previousTokenIndex]->isGivenKind(T_STRING)
                    || $tokens[$previousTokenIndex]->isGivenKind(CT::T_ARRAY_TYPEHINT)
                )
            ) {
                $constructArguments[$tokens[$i]->getContent()] = $tokens[$previousTokenIndex]->getContent();
            }
        }

        return $constructArguments;
    }

    private function getProperty(Tokens $tokens, int $key): ?array
    {
        if ($tokens[$key]->isGivenKind(T_VARIABLE)) {
            $previousTokenIndex = $tokens->getPrevNonWhitespace($key);
            $previousPreviousTokenIndex =
                $previousTokenIndex ? $tokens->getPrevNonWhitespace($previousTokenIndex) : null;
            if (
                $tokens[$previousTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                && !$tokens[$previousPreviousTokenIndex]->isGivenKind(T_COMMENT)
            ) {
                return $this->getPropertyValues($tokens, $key, $previousTokenIndex);
            } elseif (
                $tokens[$previousTokenIndex]->isGivenKind(T_STATIC)
                && $tokens[$previousPreviousTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
                && !$tokens[$tokens->getPrevNonWhitespace($previousPreviousTokenIndex)]->isGivenKind(T_COMMENT)
            ) {
                return $this->getPropertyValues($tokens, $key, $previousPreviousTokenIndex);
            }
        }

        return null;
    }

    private function getPropertyValues(Tokens $tokens, int $key, int $previousTokenIndex): array
    {
        $property['Index'] = $key;
        $property['Variable'] = $tokens[$key]->getContent();
        $index = $tokens->getPrevNonWhitespace($previousTokenIndex);
        if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
            $property['DocBlockIndex'] = $index;
        } else {
            $property['DocBlockInsertIndex'] = $previousTokenIndex - 1;
        }

        return $property;
    }

    private function insertComment(Tokens $tokens, int $insertIndex, string $propertyName)
    {
        $comment = '// TODO: "' . $propertyName . '" - ' . self::MISSING_DOC_BLOCK_CONVENTION;
        $tokens->insertSlices([
            ($insertIndex + 1) => [
                new Token([T_COMMENT, $comment]),
                new Token([
                    T_WHITESPACE,
                    $this->whitespacesConfig->getLineEnding() . $this->whitespacesConfig->getIndent(),
                ]),
            ],
        ]);
    }

    private function hasCommentAdditionalData(Token $docBlockToken): bool
    {
        return preg_match('/[^\s\*\/].*@var|@var.*\n[^\s\*\/]|@var.*\|/s', $docBlockToken->getContent()) === 1;
    }
}
