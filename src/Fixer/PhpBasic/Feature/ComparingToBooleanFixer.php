<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use Paysera\PhpCsFixerConfig\Parser\ContextualTokenBuilder;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\EmptyToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use Paysera\PhpCsFixerConfig\Parser\Entity\SeparatedItemList;
use Paysera\PhpCsFixerConfig\Parser\Entity\SimpleItemList;
use Paysera\PhpCsFixerConfig\Parser\GroupSeparatorHelper;
use Paysera\PhpCsFixerConfig\Parser\Parser;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;
use RuntimeException;
use SplFileInfo;

final class ComparingToBooleanFixer extends AbstractFixer
{
    /**
     * @var array
     */
    const BOOL_CONSTANTS = [
        'true',
        'false',
    ];
    private $parser;
    private $contextualTokenBuilder;

    public function __construct()
    {
        parent::__construct();
        $this->parser = new Parser(new GroupSeparatorHelper());
        $this->contextualTokenBuilder = new ContextualTokenBuilder();
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            '
            We do not use true/false keywords when checking variable which is already boolean.
            ',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            private function sampleFunction(bool $valid)
                            {
                                if ($valid === false) {
                                    return $valid !== true;
                                }
                                
                                if ($valid === true) {
                                    return $valid === false;
                                }
                                
                                if ($valid === true) {
                                    return false !== $valid;
                                }
                            }
                        }
                    '
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
        return 'Paysera/php_basic_feature_comparing_to_boolean';
    }

    public function getPriority()
    {
        // Should run after `ComparisonOrderFixer`
        return -10;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound([T_IS_IDENTICAL, T_IS_NOT_IDENTICAL]);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $token = $this->contextualTokenBuilder->buildFromTokens($tokens);
        $firstToken = (new EmptyToken())->setNextContextualToken($token);

        while ($token !== null) {
            if ($token->isGivenKind(T_FUNCTION)) {
                $boolVariables = array_unique(array_merge(
                    $this->getBoolVariablesFromPhpDoc($token),
                    $this->getBoolVariablesFromArguments($token)
                ));

                $groupedItem = $this->parser->parseUntil($token->nextTokenWithContent('{'), '}');

                $this->fixBooleanComparisons($groupedItem, $boolVariables);

                $token = $groupedItem->lastToken();
            }

            $token = $token->getNextToken();
        }

        $this->contextualTokenBuilder->overrideTokens($tokens, $firstToken);
    }

    private function fixBooleanComparisons(ItemInterface $functionItem, array $boolVariables)
    {
        foreach ($functionItem->getComplexItemLists() as $complexItemList) {
            if (
                !$complexItemList instanceof SeparatedItemList
                || !in_array($complexItemList->getSeparator(), ['===', '!=='], true)
            ) {
                continue;
            }

            /** @var ItemInterface[] $contentItems */
            $contentItems = iterator_to_array($complexItemList->getContentItems());
            if (count($contentItems) !== 2) {
                throw new RuntimeException(
                    sprintf('Expected 2 content items for comparison, got %s', count($contentItems))
                );
            }

            if (
                !$contentItems[0] instanceof ContextualToken
                || !$contentItems[1] instanceof ContextualToken
            ) {
                continue;
            }

            if (in_array($contentItems[0]->getContent(), self::BOOL_CONSTANTS, true)) {
                $boolConstant = $contentItems[0]->getContent();
                $possibleVariable = $contentItems[1]->getContent();
            } elseif (in_array($contentItems[1]->getContent(), self::BOOL_CONSTANTS, true)) {
                $boolConstant = $contentItems[1]->getContent();
                $possibleVariable = $contentItems[0]->getContent();
            } else {
                continue;
            }

            if (!in_array($possibleVariable, $boolVariables, true)) {
                continue;
            }

            $positiveComparison = $boolConstant === 'true';
            if ($complexItemList->getSeparator() === '!==') {
                $positiveComparison = !$positiveComparison;
            }

            $replaceToken = new ContextualToken([T_VARIABLE, $possibleVariable]);
            if (!$positiveComparison) {
                $exclamation = new ContextualToken('!');
                $exclamation->setNextContextualToken($replaceToken);
                $replaceToken = new SimpleItemList([$exclamation, $replaceToken]);
            }

            $this->contextualTokenBuilder->replaceItem($complexItemList, $replaceToken);
        }
    }

    private function getBoolVariablesFromPhpDoc(ContextualToken $functionToken): array
    {
        $docBlock = $this->getDocBlockForFunctionToken($functionToken);
        $paramAnnotations = $docBlock !== null ? $docBlock->getAnnotationsOfType('param') : [];

        $variableNames = [];
        foreach ($paramAnnotations as $annotation) {
            $types = $annotation->getTypes();
            if (
                count($types) === 1
                && in_array($types[0], ['bool', 'boolean'], true)
                && preg_match('/[ \t](?<varName>\$[a-zA-Z0-9]+)/', $annotation->getContent(), $matches) === 1
            ) {
                $variableNames[] = $matches['varName'];
            }
        }

        return $variableNames;
    }

    /**
     * @param ContextualToken $functionToken
     * @return null|DocBlock
     */
    private function getDocBlockForFunctionToken(ContextualToken $functionToken)
    {
        $token = $functionToken->previousNonWhitespaceToken();
        while ($token->isGivenKind([T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL, T_ABSTRACT, T_STATIC])) {
            $token = $token->previousNonWhitespaceToken();
        }
        if ($token->isGivenKind(T_DOC_COMMENT)) {
            return new DocBlock($token->getContent());
        }

        return null;
    }

    private function getBoolVariablesFromArguments(ContextualToken $functionToken): array
    {
        $token = $functionToken->nextTokenWithContent('(');

        $variableNames = [];
        $currentGroup = [];
        do {
            $token = $token->nextToken();
            if (in_array($token->getContent(), [',', ')'], true)) {
                if (count($currentGroup) === 2 && $currentGroup[0] === 'bool') {
                    $variableNames[] = $currentGroup[1];
                }
                $currentGroup = [];
            } elseif (!$token->isWhitespace()) {
                $currentGroup[] = $token->getContent();
            }
        } while ($token->getContent() !== ')');

        return $variableNames;
    }
}
