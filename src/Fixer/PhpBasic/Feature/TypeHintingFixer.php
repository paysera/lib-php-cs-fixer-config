<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionClass;

final class TypeHintingFixer extends AbstractFixer
{
    const CONVENTION = 'PhpBasic convention 3.18: We always type hint narrowest possible interface';
    const CONSTRUCT = '__construct';
    const THIS = '$this';

    /**
     * @var array
     */
    private $exceptions = ['EntityManager', 'Repository'];

    public function getDefinition()
    {
        return new FixerDefinition(
            'We always type hint narrowest possible interface which we use inside the function or class.',
            [
                new CodeSample(
                    '<?php
                        class NormalizerInterface
                        {
                            public function methodA();
                        }
                        
                        class DenormalizerInterface
                        {
                            public function methodB();
                        }
                        
                        class ResultNormalizer implements NormalizerInterface, DenormalizerInterface
                        {
                            public function methodA()
                            {
                                return true;
                            }
                            
                            public function methodB()
                            {
                                return true;
                            }
                        }
                    
                        class Sample
                        {
                            $private $arg1;
                        
                            public function __construct(ResultNormalizer $arg1)
                            {
                                $this->arg1 = $arg1;
                            }
                            
                            private function someFunction()
                            {
                                $this->arg1->methodA();
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_type_hinting';
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

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        if ($this->configuration['exceptions'] === true) {
            return;
        }
        if (isset($this->configuration['exceptions'])) {
            $this->exceptions = $this->configuration['exceptions'];
        }
    }

    protected function createConfigurationDefinition()
    {
        $options = new FixerOptionBuilder(
            'exceptions',
            'Sets the exceptions for which usage of narrowest interface could be skipped.'
        );

        $options = $options
            ->setAllowedTypes(['array', 'bool'])
            ->getOption()
        ;

        return new FixerConfigurationResolverRootless('exceptions', [$options]);
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $useStatements = [];
        $constructClassProperties = [];

        // Collect use statements construct class names and methods used from classes
        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_USE)) {
                $useStatements[] = $this->getUseStatementContent($tokens, $key);
            }

            if ($token->isGivenKind(T_STRING)
                && $token->getContent() === self::CONSTRUCT
                && $tokens[$tokens->getPrevMeaningfulToken($key)]->isGivenKind(T_FUNCTION)
            ) {
                $startIndex = $tokens->getNextTokenOfKind($key, ['(']);
                $endIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startIndex);
                $constructClassProperties = $this->getConstructArgumentClassesAndAssignedProperties(
                    $tokens,
                    $startIndex,
                    $endIndex
                );
            }

            if (empty($constructClassProperties)) {
                continue;
            }

            $previousTokenIndex = $tokens->getPrevMeaningfulToken($key);
            $methodIndex = $tokens->getNextMeaningfulToken($key);
            if ($token->isGivenKind(T_OBJECT_OPERATOR)
                && ($tokens[$previousTokenIndex]->isGivenKind(T_STRING)
                    || $tokens[$previousTokenIndex]->isGivenKind(T_VARIABLE))
                && $tokens[$methodIndex]->isGivenKind(T_STRING)
                && $tokens[$tokens->getNextMeaningfulToken($methodIndex)]->equals('(')
            ) {
                foreach ($constructClassProperties as &$constructClassProperty) {
                    if ($constructClassProperty['Property'] === $tokens[$previousTokenIndex]->getContent()) {
                        $constructClassProperty['Methods'][] = $tokens[$methodIndex]->getContent();
                    }
                }
            }
        }

        if (isset($endIndex) && count($constructClassProperties) > 0 && count($useStatements) > 0) {
            $this->validateConstructTypeHints($tokens, $endIndex, $useStatements, $constructClassProperties);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $endIndex
     * @param string[] $useStatements
     * @param string[] $constructClassProperties
     */
    private function validateConstructTypeHints(Tokens $tokens, $endIndex, $useStatements, $constructClassProperties)
    {
        $properInterfaces = [];

        foreach ($useStatements as $useStatement) {
            foreach ($constructClassProperties as $key => $constructClassProperty) {
                foreach ($this->exceptions as $exception) {
                    if (strpos($key, $exception) !== false) {
                        continue 2;
                    }
                }
                if (!preg_match('#' . $key . '$#', $useStatement)) {
                    continue;
                }

                if (!is_array($constructClassProperty)) {
                    continue;
                }

                if (!isset($constructClassProperty['Methods'])) {
                    continue;
                }

                try {
                    $reflectionClass = new ReflectionClass($useStatement);
                    $interfaces = $reflectionClass->getInterfaces();
                    if (empty($interfaces)) {
                        continue;
                    }

                    /** @var ReflectionClass $interface */
                    foreach (array_reverse($interfaces) as $interface) {
                        $reflectionMethods = $interface->getMethods();
                        if (empty($reflectionMethods)) {
                            continue;
                        }

                        $methods = [];
                        foreach ($reflectionMethods as $reflectionMethod) {
                            $methods[] = $reflectionMethod->getName();
                        }

                        if (count(array_intersect($constructClassProperty['Methods'], $methods)) > 0) {
                            $properInterfaces[$key] = $interface->getShortName();
                            break;
                        }
                    }
                } catch (\Exception $exception) {
                }
            }
        }

        if (count($properInterfaces) > 0) {
            $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($endIndex);
            if ($tokens[$curlyBraceStartIndex]->equals('{')) {
                $insertIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
                $this->insertComment($tokens, $insertIndex, $properInterfaces);
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $startIndex
     * @param int $endIndex
     * @return array|null
     */
    private function getConstructArgumentClassesAndAssignedProperties(Tokens $tokens, $startIndex, $endIndex)
    {
        $constructClassProperties = [];
        for ($i = $startIndex; $i < $endIndex; ++$i) {
            $classNameIndex = $tokens->getPrevMeaningfulToken($i);
            $className = $tokens[$classNameIndex]->getContent();
            if ($tokens[$i]->isGivenKind(T_VARIABLE)
                && $tokens[$classNameIndex]->isGivenKind(T_STRING)
            ) {
                $constructClassProperties[$className]['Variable'] = $tokens[$i]->getContent();
            }
        }

        $curlyBraceStartIndex = $tokens->getNextMeaningfulToken($endIndex);
        if (!$tokens[$curlyBraceStartIndex]->equals('{') || empty($constructClassProperties)) {
            return null;
        }

        $curlyBraceEndIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $curlyBraceStartIndex);
        for ($i = $curlyBraceStartIndex; $i < $curlyBraceEndIndex; ++$i) {
            $assignIndex = $tokens->getPrevMeaningfulToken($i);
            if ($tokens[$i]->isGivenKind(T_VARIABLE)
                && $tokens[$assignIndex]->equals('=')
            ) {
                $propertyIndex = $tokens->getPrevMeaningfulToken($assignIndex);
                if (!$tokens[$propertyIndex]->isGivenKind(T_STRING)) {
                    continue;
                }

                $objectOperatorIndex = $tokens->getPrevMeaningfulToken($propertyIndex);
                if (!$tokens[$objectOperatorIndex]->isGivenKind(T_OBJECT_OPERATOR)) {
                    continue;
                }

                $thisIndex = $tokens->getPrevMeaningfulToken($objectOperatorIndex);
                if (!$tokens[$thisIndex]->isGivenKind(T_VARIABLE) && $tokens[$thisIndex]->getContent() !== self::THIS) {
                    continue;
                }

                foreach ($constructClassProperties as $key => $constructClassProperty) {
                    if ($constructClassProperty['Variable'] === $tokens[$i]->getContent()) {
                        $constructClassProperties[$key]['Property'] = $tokens[$propertyIndex]->getContent();
                        break;
                    }
                }
            }
        }

        foreach ($constructClassProperties as $key => &$constructClassProperty) {
            if (!isset($constructClassProperty['Property'])) {
                unset($constructClassProperties[$key]);
            }
        }

        return $constructClassProperties;
    }

    /**
     * @param Tokens $tokens
     * @param int $insertIndex
     * @param array $properInterfaces
     */
    private function insertComment(Tokens $tokens, $insertIndex, $properInterfaces)
    {
        $interfacesComment = '';
        foreach ($properInterfaces as $key => $interface) {
            $interfacesComment .= $key . '(' . $interface . ')';
            if (count($properInterfaces) > 1) {
                $interfacesComment .= ' | ';
            }
        }

        $comment = '/* TODO: Class(Narrowest Interface): "' . $interfacesComment . '" - ' . self::CONVENTION . ' */';
        if (!$tokens[$tokens->getNextNonWhitespace($insertIndex)]->isGivenKind(T_COMMENT)) {
            $tokens->insertAt($insertIndex + 1, new Token([T_COMMENT, $comment]));
            $tokens->insertAt($insertIndex + 1, new Token([T_WHITESPACE, ' ']));
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $useIndex
     * @return string
     */
    private function getUseStatementContent(Tokens $tokens, $useIndex)
    {
        $index = $useIndex + 1;
        $useStatementContent = '';
        while (!$tokens[$index + 1]->equals(';')) {
            $index++;
            $useStatementContent .= $tokens[$index]->getContent();
        }
        return $useStatementContent;
    }
}
