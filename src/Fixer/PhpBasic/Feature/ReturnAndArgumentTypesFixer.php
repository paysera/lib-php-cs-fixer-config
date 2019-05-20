<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use Doctrine\Common\Inflector\Inflector;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\Annotation;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ReturnAndArgumentTypesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    const NO_MIXED_VOID = 'TODO: we always return something or always nothing (https://bit.ly/psg-return-and-argument-types)';
    const MULTIPLE_TYPES = 'TODO: we do not use multiple types (https://bit.ly/psg-return-and-argument-types)';
    const MULTIPLE_ENTITIES = 'TODO: use single interface or common class instead (https://bit.ly/psg-return-and-argument-types)';
    const REPOSITORY = 'Repository';

    const COLLECTION_TYPE_REGEXP = '/Collection$|Generator$|^array$|\[\]$|<.*>/';

    private $scalarTypes;
    private $strictValues;

    public function __construct()
    {
        parent::__construct();
        $this->scalarTypes = [
            'array',
            'callable',
            'bool',
            'boolean',
            'float',
            'int',
            'integer',
            'string',
            'resource',
        ];
        $this->strictValues = [
            T_CONSTANT_ENCAPSED_STRING,
            T_DNUMBER,
            T_LNUMBER,
        ];
    }

    // Excluding class namespaces by passing int|Entity by PhpBasic convention `3.17.3. Passing ID`
    private static function getPassingIdNamespaceExclusions()
    {
        return [
            self::REPOSITORY,
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
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
                             * @param int|MyClass $arg1
                             * @param OneClass|AnotherClass $arg2
                             * @return void|int
                             */
                            public function someFunction($arg1, $arg2)
                            {
                                if ($arg1) {
                                    return $arg1;
                                } elseif ($arg2) {
                                    return;
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

    public function applyFix(SplFileInfo $file, Tokens $tokens)
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
            if (
                $token->isGivenKind(T_STRING)
                && $tokens[$key + 1]->equals('(')
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

        $returnsValue = false;
        $returnsVoid = false;
        $firstReturnValue = null;
        for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; $i++) {
            if ($tokens[$i]->isGivenKind(T_RETURN)) {
                $tokenIndex = $tokens->getNextMeaningfulToken($i);
                $returnValue = $tokens[$tokenIndex];
                $voidReturn = $returnValue->getContent() === ';';

                if ($voidReturn) {
                    $returnsVoid = true;
                    $semicolonIndex = $tokenIndex;
                } else {
                    $returnsValue = true;
                    $firstReturnValue = $firstReturnValue ?? $returnValue->getId();
                    $semicolonIndex = $tokens->getNextTokenOfKind($tokenIndex, [';']);
                }

                if ($voidReturn && $returnsValue) {
                    $this->insertComment($tokens, $semicolonIndex, self::NO_MIXED_VOID);
                    continue;
                } elseif (!$voidReturn && $returnsVoid) {
                    $this->insertComment($tokens, $semicolonIndex, self::NO_MIXED_VOID);
                    continue;
                }

                if (
                    !$returnValue->isGivenKind($this->strictValues)
                    && $returnValue->getContent() !== 'true'
                    && $returnValue->getContent() !== 'false'
                ) {
                    continue;
                }

                if ($returnValue->getId() !== $firstReturnValue) {
                    $this->insertComment($tokens, $semicolonIndex, self::MULTIPLE_TYPES);
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

        foreach ($annotations as $annotation) {
            if (
                strpos($annotation->getContent(), self::MULTIPLE_TYPES) !== false
                || strpos($annotation->getContent(), self::MULTIPLE_ENTITIES) !== false
                || strpos($annotation->getContent(), self::NO_MIXED_VOID) !== false
            ) {
                continue;
            }

            $types = array_values(array_diff($annotation->getTypes(), ['null', '$this', 'self']));
            $warning = $this->getTypeUsageWarning($types, $classNamespace);
            if ($warning !== null) {
                $this->insertReturnAnnotationWarning(
                    $tokens,
                    $docBlockIndex,
                    $annotation,
                    $warning
                );
            }
        }
    }

    private function getTypeUsageWarning(array $types, $classNamespace)
    {
        if (in_array('mixed', $types, true)) {
            return self::MULTIPLE_TYPES;
        }

        if (count($types) <= 1) {
            return null;
        }

        if (in_array('void', $types, true)) {
            return self::NO_MIXED_VOID;
        }

        $typeCount = count($types);

        $scalarTypesFound = array_intersect($types, $this->scalarTypes);
        $intFound = count(array_intersect(['int', 'integer'], $scalarTypesFound)) > 0;
        $scalarTypesCount = count($scalarTypesFound);
        $otherTypesCount = $typeCount - $scalarTypesCount;

        // handle exception with int|Entity passing to Repositories
        if (
            $otherTypesCount === 1
            && $scalarTypesCount === 1
            && $intFound
            && in_array($classNamespace, self::getPassingIdNamespaceExclusions(), true)
        ) {
            return null;
        }

        // handle array|Collection|Item[] case
        $arrayTypes = array_filter($types, function (string $type) {
            if (preg_match(self::COLLECTION_TYPE_REGEXP, $type) === 1) {
                return true;
            }

            $typeWord = trim($type, ' []');

            return $typeWord === Inflector::pluralize($typeWord);
        });
        if (count($arrayTypes) === $typeCount) {
            return null;
        }

        if ($scalarTypesCount > 0) {
            return self::MULTIPLE_TYPES;
        }

        return self::MULTIPLE_ENTITIES;
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
     * @param string $comment
     */
    private function insertComment(Tokens $tokens, int $insertIndex, string $comment)
    {
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt(++$insertIndex, new Token([T_WHITESPACE, ' ']));
            $tokens->insertAt(++$insertIndex, new Token([T_COMMENT, '// ' . $comment]));
        }
    }
}
