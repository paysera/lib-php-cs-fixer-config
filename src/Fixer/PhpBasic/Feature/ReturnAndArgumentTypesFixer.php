<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class ReturnAndArgumentTypesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    const TYPECAST_CONVENTION = 'PhpBasic convention 3.17.1: We always return value of one type';
    const MULTIPLE_TYPES = '@TODO: we do not use multiple types';
    const MULTIPLE_ENTITIES = '@TODO: use single interface or common class instead';
    const RETURN_VOID_CONVENTION = '@TODO: return only void or type with null';
    const ENTITY = 'Entity';
    const REPOSITORY = 'Repository';

    /**
     * @var array
     */
    private $scalarTypes = [
        'array',
        'callable',
        'bool',
        'boolean',
        'float',
        'int',
        'integer',
        'string',
    ];

    /**
     * @var array
     */
    private $strictValues = [
        T_CONSTANT_ENCAPSED_STRING,
        T_DNUMBER,
        T_LNUMBER,
    ];

    // Excluding class namespaces by passing int|Entity by PhpBasic convention `3.17.3. Passing ID`
    private static function getPassingIdNamespaceExclusions()
    {
        return [
            self::ENTITY,
            self::REPOSITORY,
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition('
            We always return value of one type. Optionally, we can return null when using any other return type, too.
            
            For example, we can*not* return boolean|string or SuccessResult|FailureResult
            (if SuccessResult and FailureResult has no common class or interface;
            if they do, we document to return that interface instead).
            
            We can return SomeClass|null or string|null.
            ',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            /*
                             * @param int $arg1
                             * @param string $arg2
                             * @return string|int
                             */
                            public function someFunction($arg1, $arg2)
                            {
                                if ($arg1) {
                                    return $arg1;
                                } elseif ($arg2) {
                                    return $arg2;
                                }
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_return_and_argument_types';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $classNamespace = null;
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $semicolonIndex = $tokens->getNextTokenOfKind($key, [';']);
                if ($tokens[$semicolonIndex - 1]->isGivenKind(T_STRING)) {
                    $classNamespace = $tokens[$semicolonIndex - 1]->getContent();
                }
            }

            $functionTokenIndex = $tokens->getPrevNonWhitespace($key);
            $visibilityTokenIndex = $tokens->getPrevNonWhitespace($functionTokenIndex);
            if ($token->isGivenKind(T_STRING) && $tokens[$key + 1]->equals('(')
                && $tokens[$functionTokenIndex]->isGivenKind(T_FUNCTION)
                && $tokens[$visibilityTokenIndex]->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE])
            ) {
                $index = $tokens->getPrevNonWhitespace($visibilityTokenIndex);
                $parenthesesEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $key + 1);
                $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($parenthesesEndIndex);
                $docBlockIndex = null;
                if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $index;
                } elseif ($tokens[$tokens->getPrevNonWhitespace($index)]->isGivenKind(T_DOC_COMMENT)) {
                    $docBlockIndex = $tokens->getPrevNonWhitespace($index);
                }

                if ($docBlockIndex !== null) {
                    $this->validateDocBlockTypes($tokens, $docBlockIndex, $classNamespace);
                }

                if ($tokens[$curlyBraceStartIndex]->equals('{')) {
                    $this->validateReturnTypes($tokens, $curlyBraceStartIndex);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $curlyBraceStartIndex
     */
    private function validateReturnTypes(Tokens $tokens, $curlyBraceStartIndex)
    {
        $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);

        $firstReturnValue = null;
        for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; ++$i) {
            if ($tokens[$i]->isGivenKind(T_RETURN)) {
                $returnValue = $tokens[$tokens->getNextMeaningfulToken($i)];
                if ($returnValue->getContent() === 'null') {
                    continue;
                }

                if (!$returnValue->isGivenKind($this->strictValues)
                    && $returnValue->getContent() !== 'true'
                    && $returnValue->getContent() !== 'false'
                ) {
                    break;
                }

                if ($firstReturnValue === null) {
                    $firstReturnValue = $returnValue->getId();
                    continue;
                }

                if ($returnValue->getId() !== $firstReturnValue) {
                    $this->insertComment(
                        $tokens,
                        $tokens->getNextTokenOfKind($i, [';']),
                        $returnValue->getContent()
                    );
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param string $classNamespace
     */
    private function validateDocBlockTypes(Tokens $tokens, $docBlockIndex, $classNamespace)
    {
        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
        $annotations = $docBlock->getAnnotationsOfType(['return', 'param']);

        if (!isset($annotations)) {
            return;
        }

        foreach ($annotations as $annotation) {
            if (preg_match(
                '#' . self::MULTIPLE_TYPES . '|' . self::MULTIPLE_ENTITIES . '|' . self::RETURN_VOID_CONVENTION . '#',
                $annotation->getContent()
            )) {
                continue;
            }

            $types = $annotation->getTypes();
            $typeCount = count($types);

            $scalarTypesFound = array_intersect($types, $this->scalarTypes);
            $scalarTypesCount = count($scalarTypesFound);

            $objectTypesFound = array_diff(
                $types,
                array_merge($this->scalarTypes, ['null', 'void', 'self', '$this', 'mixed'])
            );
            $objectTypesCount = count($objectTypesFound);

            $nullFound = in_array('null', $types, true);
            $voidFound = in_array('void', $types, true);
            $selfFound = in_array('self', $types, true);
            $thisFound = in_array('$this', $types, true);
            $mixedFound = in_array('mixed', $types, true);
            $intFound = (bool)array_intersect(['int', 'integer'], $scalarTypesFound);

            if ($scalarTypesCount > 1
                || $mixedFound
                || ($typeCount > 1 && $scalarTypesCount === 1 && $objectTypesCount > 1 && !$nullFound)
                || ($typeCount > 1 && $scalarTypesCount === 1 && $objectTypesCount === 1
                    && (!$intFound
                        || ($intFound && !in_array($classNamespace, self::getPassingIdNamespaceExclusions(), true))))
            ) {
                $this->insertReturnAnnotationWarning(
                    $tokens,
                    $docBlockIndex,
                    $annotation,
                    self::MULTIPLE_TYPES
                );
            }

            if ($voidFound && ($scalarTypesCount > 0 || $nullFound || $selfFound || $thisFound || $mixedFound)) {
                $this->insertReturnAnnotationWarning(
                    $tokens,
                    $docBlockIndex,
                    $annotation,
                    self::RETURN_VOID_CONVENTION
                );
            }

            if ($scalarTypesCount === 0
                && (($typeCount > 1 && !$nullFound && !$selfFound && !$thisFound && !$mixedFound)
                    || ($typeCount > 2 && ($nullFound || $selfFound || $thisFound || $mixedFound)))
            ) {
                $this->insertReturnAnnotationWarning(
                    $tokens,
                    $docBlockIndex,
                    $annotation,
                    self::MULTIPLE_ENTITIES
                );
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param Annotation $returnAnnotation
     * @param string $warning
     */
    private function insertReturnAnnotationWarning(
        Tokens $tokens,
        $docBlockIndex,
        Annotation $returnAnnotation,
        $warning
    ) {
        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
        $lines = $docBlock->getLines();
        $replacement = preg_replace('#\\n$#', ' ', $returnAnnotation->getContent());
        $lines[$returnAnnotation->getEnd()] = new Line(
            $replacement . $warning . $this->whitespacesConfig->getLineEnding()
        );
        $tokens[$docBlockIndex]->setContent(implode('', $lines));
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param string $returnValue
     */
    private function insertComment(Tokens $tokens, $insertIndex, $returnValue)
    {
        $comment = '// TODO: ' . $returnValue . ' - ' . self::TYPECAST_CONVENTION;
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt(++$insertIndex, new Token([T_WHITESPACE, ' ']));
            $tokens->insertAt(++$insertIndex, new Token([T_COMMENT, $comment]));
        }
    }
}
