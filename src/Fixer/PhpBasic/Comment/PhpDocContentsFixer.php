<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Comment;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class PhpDocContentsFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    const MISSING_RETURN = '@TODO: missing return statement';
    const MISSING_TYPECAST = '@TODO: missing parameter typecast';
    const MISSING_VARIABLE = '@TODO: missing parameter variable';

    /**
     * @var array
     */
    private $scalarTypes;

    public function __construct()
    {
        parent::__construct();
        $this->scalarTypes = [
            'array',
            'string',
            'int',
            'float',
            'bool',
            'callable',
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            If we use phpdoc comment, it must contain all information about parameters,
            return type and exceptions that the method throws.
            
            If method does not return anything, we skip @return comment.
            ',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            /**
                             * @param $arg1
                             * @param $arg2
                             */
                            public function __construct($arg1, $arg2)
                            {
                                if ($arg1) {
                                    throw new \Exception();
                                } else {
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
        return 'Paysera/php_basic_comment_php_doc_contents';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function getPriority()
    {
        // Should run after all PhpDoc Fixers
        return -50;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_FUNCTION);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
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

                if (
                    $docBlockIndex !== null
                    && $tokens[$curlyBraceStartIndex]->equals('{')
                    && !preg_match('#@inheritdoc#', strtolower($tokens[$docBlockIndex]->getContent()))
                ) {
                    $this->validateDocBlockParameters($tokens, $key + 1, $parenthesesEndIndex, $docBlockIndex);
                    $this->validateDocBlockThrowReturnStatements($tokens, $curlyBraceStartIndex, $docBlockIndex);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $parenthesesStartIndex
     * @param int $parenthesesEndIndex
     * @param int $docBlockIndex
     */
    private function validateDocBlockParameters(
        Tokens $tokens,
        $parenthesesStartIndex,
        $parenthesesEndIndex,
        $docBlockIndex
    ) {
        $parameters = [];
        // Collect parameters
        for ($i = $parenthesesStartIndex; $i < $parenthesesEndIndex; $i++) {
            if ($tokens[$i]->isGivenKind(T_VARIABLE)) {
                $parameter = [];
                $previousTokenIndex = $tokens->getPrevMeaningfulToken($i);
                if ($tokens[$previousTokenIndex]->isGivenKind(T_STRING)) {
                    $index = $previousTokenIndex;
                    while (
                        !$tokens[$index]->equals('(')
                        && !$tokens[$index]->equals(',')
                        && !$tokens[$index]->isWhitespace()
                    ) {
                        $parameter['Typecast'] = $tokens[$index]->getContent() . ($parameter['Typecast'] ?? '');
                        $index--;
                    }
                } elseif (in_array($tokens[$previousTokenIndex]->getContent(), $this->scalarTypes, true)) {
                    $parameter['Typecast'] = $tokens[$previousTokenIndex]->getContent();
                }

                $parameter['Nullable'] = false;
                $nextTokenIndex = $tokens->getNextMeaningfulToken($i);
                if (
                    $tokens[$nextTokenIndex]->equals('=')
                    && $tokens[$tokens->getNextMeaningfulToken($nextTokenIndex)]->getContent() === 'null'
                ) {
                    $parameter['Nullable'] = true;
                }
                $parameter['Variable'] = $tokens[$i]->getContent();
                $parameters[] = $parameter;
            }
        }

        // Validate docBlock according to parameters
        foreach ($parameters as $parameter) {
            $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
            $lines = $docBlock->getLines();

            foreach ($docBlock->getAnnotationsOfType('param') as $annotation) {
                // If object is optional and does not have null in docBlock - add it
                if (
                    preg_match(
                        '#^[^$]+@param\s([^$].*?)\s\\' . $parameter['Variable'] . '#m',
                        $annotation->getContent()
                    )
                    && !in_array('null', $annotation->getTypes(), true)
                    && $parameter['Nullable']
                ) {
                    $annotation->setTypes(array_merge($annotation->getTypes(), ['null']));
                    $tokens[$docBlockIndex]->setContent(implode('', $lines));
                    continue;
                }

                // Add missing parameter typecast
                if (preg_match(
                    '#^[^$]+@param+.(\\' . $parameter['Variable'] . ').*$#m',
                    $annotation->getContent()
                )) {
                    $annotationContent = $annotation->getContent();
                    if (isset($parameter['Typecast'])) {
                        if ($parameter['Nullable']) {
                            $replacement = preg_replace(
                                '#\\' . $parameter['Variable'] . '#',
                                $parameter['Typecast'] . '|null ' . $parameter['Variable'],
                                $annotationContent
                            );
                        } else {
                            $replacement = preg_replace(
                                '#\\' . $parameter['Variable'] . '#',
                                $parameter['Typecast'] . ' ' . $parameter['Variable'],
                                $annotationContent
                            );
                        }
                        $lines[$annotation->getEnd()] = new Line($replacement);
                        $tokens[$docBlockIndex]->setContent(implode('', $lines));
                        continue;
                    }

                    // Add missing parameter typecast warning
                    if (!preg_match(
                        '#\\' . $parameter['Variable'] . ' ' . self::MISSING_TYPECAST . '#',
                        $tokens[$docBlockIndex]->getContent()
                    )) {
                        $replacement = preg_replace('#\\n#', ' ', $annotationContent);
                        $lines[$annotation->getEnd()] = new Line(
                            $replacement . self::MISSING_TYPECAST . $this->whitespacesConfig->getLineEnding()
                        );
                        $tokens[$docBlockIndex]->setContent(implode('', $lines));
                    }
                    continue;
                }
            }

            if (preg_match('#@param\s(?!.*\$).*$#m', $tokens[$docBlockIndex]->getContent(), $matches)) {
                // Add missing parameter variable warning
                $this->insertParamAnnotationWarning($tokens, $docBlockIndex, $matches[0], self::MISSING_VARIABLE);
                continue;
            } elseif (!preg_match('#\\' . $parameter['Variable'] . '#', $tokens[$docBlockIndex]->getContent())) {
                // Add missing parameter
                if (isset($parameter['Typecast'])) {
                    $this->insertParamAnnotation(
                        $tokens,
                        $docBlockIndex,
                        $parameter['Typecast'],
                        $parameter['Variable']
                    );
                } else {
                    $this->insertParamAnnotation(
                        $tokens,
                        $docBlockIndex,
                        $parameter['Variable'],
                        self::MISSING_TYPECAST
                    );
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param string $match
     * @param string $warning
     */
    private function insertParamAnnotationWarning(Tokens $tokens, $docBlockIndex, $match, $warning)
    {
        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
        $lines = $docBlock->getLines();
        /** @var Line $annotation */
        foreach ($lines as $index => &$annotation) {
            $annotationContent = $annotation->getContent();
            if (
                !preg_match('#\\' . $match . '#', $annotationContent)
                || preg_match('#' . $warning . '#', $annotationContent)
            ) {
                continue;
            }
            $replacement = preg_replace('#\\n#', ' ', $annotationContent);
            $lines[$index] = new Line(
                $replacement . $warning . $this->whitespacesConfig->getLineEnding()
            );
            $tokens[$docBlockIndex]->setContent(implode('', $lines));
            break;
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param string $typecast
     * @param string $variable
     */
    private function insertParamAnnotation(Tokens $tokens, $docBlockIndex, $typecast, $variable)
    {
        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
        $lines = $docBlock->getLines();
        preg_match('/^(\s*).*$/', $lines[count($lines) - 1]->getContent(), $indent);

        $missingParam[] = new Line(sprintf(
            '%s* @param %s %s%s',
            $indent[1],
            $typecast,
            $variable,
            $this->whitespacesConfig->getLineEnding()
        ));

        array_splice(
            $lines,
            count($lines) - 1,
            0,
            $missingParam
        );
        $tokens[$docBlockIndex]->setContent(implode('', $lines));
    }

    /**
     * @param Tokens $tokens
     * @param int $curlyBraceStartIndex
     * @param int $docBlockIndex
     */
    private function validateDocBlockThrowReturnStatements(Tokens $tokens, $curlyBraceStartIndex, $docBlockIndex)
    {
        $docBlock = new DocBlock($tokens[$docBlockIndex]->getContent());
        $returnStatementExists = false;
        $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
        $exceptions = [];

        for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; $i++) {
            $nextTokenIndex = $tokens->getNextMeaningfulToken($i);
            $nextNextTokenIndex = $tokens->getNextMeaningfulToken($nextTokenIndex);

            // Collect Exception namespace
            if (
                $tokens[$i]->isGivenKind(T_STRING)
                && $tokens[$nextTokenIndex]->isGivenKind(T_VARIABLE)
                && preg_match('#Exception#', $tokens[$i]->getContent())
            ) {
                $index = $i;
                $namespace = '';
                while (!$tokens[$index]->equals('(')) {
                    $namespace = $tokens[$index]->getContent() . $namespace;
                    $index--;
                }
                $exceptions[$namespace] = $tokens[$nextTokenIndex]->getContent();
            }

            if (
                $tokens[$i]->isGivenKind(T_THROW)
                && $tokens[$nextTokenIndex]->isGivenKind([T_VARIABLE])
                && $tokens[$nextNextTokenIndex]->equals(';')
            ) {
                $docBlock = $this->validateThrowAnnotation(
                    $tokens,
                    $docBlockIndex,
                    $nextTokenIndex,
                    $exceptions,
                    $docBlock
                );
            }

            if (
                $tokens[$i]->isGivenKind(T_THROW)
                && $tokens[$nextTokenIndex]->isGivenKind(T_NEW)
                && $tokens[$nextNextTokenIndex]->isGivenKind([T_STRING, T_NS_SEPARATOR])
            ) {
                $docBlock = $this->validateThrowNewAnnotation($tokens, $docBlockIndex, $nextNextTokenIndex, $docBlock);
            }

            if ($tokens[$i]->isGivenKind(T_RETURN) && !$tokens[$nextTokenIndex]->equals(';')) {
                $returnStatementExists = true;
                $docBlock = $this->insertReturnAnnotationWarning($tokens, $docBlockIndex, $docBlock);
            }
        }

        // Removing @return if function has no return statement or has plain - return;
        $returnAnnotations = $docBlock->getAnnotationsOfType('return');
        if (!$returnStatementExists && isset($returnAnnotations[0])) {
            $returnAnnotations[0]->remove();
            $tokens[$docBlockIndex]->setContent($docBlock->getContent());
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param DocBlock $docBlock
     * @return DocBlock
     */
    private function insertReturnAnnotationWarning(Tokens $tokens, $docBlockIndex, $docBlock)
    {
        $lines = $docBlock->getLines();
        preg_match('/^(\s*).*$/', $lines[count($lines) - 1]->getContent(), $indent);
        $returnAnnotations = $docBlock->getAnnotationsOfType('return');

        if (
            !preg_match('#' . self::MISSING_RETURN . '#', $tokens[$docBlockIndex]->getContent())
            && !isset($returnAnnotations[0])
            && isset($indent[1])
        ) {
            $missingReturn[] = new Line(sprintf(
                '%s* %s%s',
                $indent[1],
                self::MISSING_RETURN,
                $this->whitespacesConfig->getLineEnding()
            ));
            array_splice($lines, count($lines) - 1, 0, $missingReturn);
            $tokens[$docBlockIndex]->setContent(implode('', $lines));
        }
        return new DocBlock($tokens[$docBlockIndex]->getContent());
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param int $variableIndex
     * @param array $exceptions
     * @param DocBlock $docBlock
     * @return DocBlock
     */
    private function validateThrowAnnotation(Tokens $tokens, $docBlockIndex, $variableIndex, $exceptions, $docBlock)
    {
        $exception = array_search($tokens[$variableIndex]->getContent(), $exceptions, true);
        $this->insertThrowsAnnotation($tokens, $docBlockIndex, $exception, $docBlock);

        return new DocBlock($tokens[$docBlockIndex]->getContent());
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param int $namespaceStartIndex
     * @param DocBlock $docBlock
     * @return DocBlock
     */
    private function validateThrowNewAnnotation(Tokens $tokens, $docBlockIndex, $namespaceStartIndex, $docBlock)
    {
        $index = $namespaceStartIndex;
        $namespaceContent = '';

        while (!$tokens[$index]->equals('(')) {
            $namespaceContent .= $tokens[$index]->getContent();
            $index++;
        }
        $this->insertThrowsAnnotation($tokens, $docBlockIndex, $namespaceContent, $docBlock);

        return new DocBlock($tokens[$docBlockIndex]->getContent());
    }

    /**
     * @param Tokens $tokens
     * @param int $docBlockIndex
     * @param string $namespaceContent
     * @param DocBlock $docBlock
     */
    private function insertThrowsAnnotation(Tokens $tokens, $docBlockIndex, $namespaceContent, $docBlock)
    {
        $lines = $docBlock->getLines();
        preg_match('/^(\s*).*$/', $lines[count($lines) - 1]->getContent(), $indent);

        $lastNamespace = explode('\\', $namespaceContent);
        $lastNamespace = $lastNamespace[count($lastNamespace) - 1];

        preg_match(
            '#@throws\s(\\\?[A-z0-9_\\\]*' . $lastNamespace . ')+($|\|)#m',
            $tokens[$docBlockIndex]->getContent(),
            $matches
        );

        if (!isset($matches[1]) && isset($indent[1])) {
            $throwLine[] = new Line(sprintf(
                '%s* @throws %s%s',
                $indent[1],
                $namespaceContent,
                $this->whitespacesConfig->getLineEnding()
            ));

            array_splice($lines, count($lines) - 1, 0, $throwLine);
            $tokens[$docBlockIndex]->setContent(implode('', $lines));
        }
    }
}
