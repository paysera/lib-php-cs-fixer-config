<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use Paysera\PhpCsFixerConfig\Parser\Entity\ComplexItemList;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\ContextualTokenBuilder;
use Paysera\PhpCsFixerConfig\Parser\Entity\EmptyToken;
use Paysera\PhpCsFixerConfig\Parser\GroupSeparatorHelper;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use Paysera\PhpCsFixerConfig\Parser\Parser;
use Paysera\PhpCsFixerConfig\Parser\Entity\SeparatedItemList;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class SplittingInSeveralLinesFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
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
            'Formats new lines, whitespaces and operators as needed when splitting in several lines',
            [
                new CodeSample('
                <?php
                    class Sample
                    {
                        public function sampleFunction()
                        {
                            $a = 1;
                            $b = 2;
                            $c = 3;
                            $d = 4;
                            
                            if ($a === 1) {
                                if ($b === 2) {
                                    in_array($a, [1,
                                        2, 3, 4,5,  ]);
                                }
                            }
                            
                            in_array($a, [1,
                                2, 3, 4,5  ], true);

                            return ((
                                $a
                                &&
                                $b
                            ) ||
                                (
                                $c
                                &&
                                $d
                            ));
                        }
                    }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_splitting_in_several_lines';
    }

    public function getPriority()
    {
        // Should run before TrailingCommaInMultilineArrayFixer
        return 1;
    }

    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    /**
     * @param SplFileInfo $file
     * @param Tokens|Token[] $tokens
     */
    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $startEndTokens = [
            '=' => ';',
            'return' => ';',
            '(' => ')',
            '[' => ']',
        ];

        $firstToken = $this->contextualTokenBuilder->buildFromTokens($tokens);

        $token = $firstToken;
        do {
            foreach ($startEndTokens as $startValue => $endValue) {
                if ($token->getContent() === $startValue) {
                    $groupedItem = $this->parser->parseUntil($token, $endValue);
                    $this->fixWhitespaceForItem($groupedItem);

                    $token = $groupedItem->lastToken();
                }
            }

            $token = $token->getNextToken();
        } while ($token !== null);

        $this->contextualTokenBuilder->overrideTokens($tokens, $firstToken);
    }

    private function fixWhitespaceForItem(ItemInterface $groupedItem)
    {
        $standardIndent = $this->whitespacesConfig->getIndent();

        $itemLists = $groupedItem->getComplexItemLists();
        foreach ($itemLists as $itemList) {
            if (!$itemList->isSplitIntoSeveralLines()) {
                continue;
            }

            if ($this->isItemListUnsupported($itemList)) {
                continue;
            }

            $prefixItem = $itemList->getFirstPrefixItem();
            $tokenForIndent = $prefixItem !== null
                ? $prefixItem->firstToken()
                : $itemList->firstToken()->previousNonWhitespaceToken();
            $firstLineIndent = $tokenForIndent->getLineIndent();

            $indent = "\n" . $firstLineIndent . $standardIndent;
            $lastIndent = "\n" . $firstLineIndent;

            $skipPrefixHandling = $itemList instanceof SeparatedItemList && $itemList->getSeparator() === '->';

            if (!$skipPrefixHandling) {
                $this->ensureContentForPrefixWhitespace($itemList, $indent);
            }
            $this->ensureContentForPostfixWhitespace($itemList, $lastIndent);

            if ($itemList instanceof SeparatedItemList) {
                $this->fixSeparators($itemList, $indent);
            }
        }
    }

    private function ensureContentForPrefixWhitespace(ComplexItemList $itemList, string $content)
    {
        $prefixWhitespaceItem = $itemList->getFirstPrefixWhitespaceItem();

        $prefixWhitespaceToken = null;
        if ($prefixWhitespaceItem !== null) {
            $prefixWhitespaceToken = $prefixWhitespaceItem->firstToken();
            if (!$prefixWhitespaceToken->isWhitespace()) {
                $prefixWhitespaceToken = null;
            }
        }

        if ($prefixWhitespaceToken !== null) {
            if ($prefixWhitespaceItem->lastToken()->getContent() !== $content) {
                $prefixWhitespaceToken->replaceWith(new ContextualToken($content));
            }
            return;
        }

        $token = new ContextualToken($content);

        $prefixItem = $itemList->getFirstPrefixItem();
        if ($prefixItem !== null) {
            $prefixItem->lastToken()->insertAfter($token);
            return;
        }

        $firstToken = $itemList->firstToken();
        $previousToken = $firstToken->previousToken();
        if ($previousToken instanceof EmptyToken) {
            $beforePreviousToken = $previousToken->previousToken();
            if ($beforePreviousToken !== null && $beforePreviousToken->isWhitespace()) {
                $beforePreviousToken->replaceWith($token);
            } else {
                $previousToken->insertBefore($token);
            }
        } elseif ($previousToken === null || !$previousToken->isWhitespace()) {
            $firstToken->insertBefore($token);
        }
    }

    private function ensureContentForPostfixWhitespace(ComplexItemList $itemList, string $content)
    {
        $postfixWhitespaceItem = $itemList->getFirstPostfixWhitespaceItem();

        $postfixWhitespaceToken = null;
        if ($postfixWhitespaceItem !== null) {
            $postfixWhitespaceToken = $postfixWhitespaceItem->lastToken();
            if (!$postfixWhitespaceToken->isWhitespace()) {
                $postfixWhitespaceToken = null;
            }
        }

        if ($postfixWhitespaceToken !== null) {
            if ($postfixWhitespaceToken->getContent() !== $content) {
                $postfixWhitespaceToken->replaceWith(new ContextualToken($content));
            }
            return;
        }

        $token = new ContextualToken($content);

        $postfixItem = $itemList->getFirstPostfixItem();
        if ($postfixItem !== null) {
            $postfixItem->firstToken()->insertBefore($token);
            return;
        }

        $lastToken = $itemList->lastToken();
        $nextToken = $lastToken->nextToken();
        if ($nextToken === null || (!$nextToken->isWhitespace() && !$nextToken instanceof EmptyToken)) {
            $lastToken->insertAfter($token);
        }
    }

    private function fixSeparators(SeparatedItemList $itemList, string $indent)
    {
        $separator = $itemList->getSeparator();

        if ($separator === '->') {
            $whitespaceBefore = $indent;
            $whitespaceAfter = null;
            $forceWhitespace = false;
        } elseif ($separator === ',') {
            $whitespaceBefore = null;
            $whitespaceAfter = $indent;
            $forceWhitespace = true;
        } else {
            $whitespaceBefore = $indent;
            $whitespaceAfter = ' ';
            $forceWhitespace = true;
        }

        foreach ($itemList->getSeparatorItems() as $item) {
            $this->fixWhitespaceBefore($item, $whitespaceBefore, $forceWhitespace);
            $this->fixWhitespaceAfter($item, $whitespaceAfter, $forceWhitespace);
        }

        $separatorAfterContents = $itemList->getSeparatorAfterContents();
        if ($separatorAfterContents !== null) {
            $this->fixWhitespaceBefore($separatorAfterContents, $whitespaceBefore, $forceWhitespace);
        }
    }

    private function isItemListUnsupported(ComplexItemList $itemList)
    {
        return (
            $itemList instanceof SeparatedItemList
            && in_array($itemList->getSeparator(), ['.', '?', ':'], true)
        );
    }

    /**
     * @param ItemInterface $item
     * @param string|null $whitespaceBefore
     * @param bool $forceWhitespace
     */
    private function fixWhitespaceBefore(ItemInterface $item, $whitespaceBefore, bool $forceWhitespace)
    {
        $firstToken = $item->firstToken();
        if ($firstToken->isWhitespace()) {
            $this->replaceWithIfNeeded($firstToken, $whitespaceBefore);
        } elseif ($forceWhitespace && $whitespaceBefore !== null) {
            $firstToken->insertBefore(new ContextualToken($whitespaceBefore));
        }
    }

    /**
     * @param ItemInterface $item
     * @param string|null $whitespaceAfter
     * @param bool $forceWhitespace
     */
    private function fixWhitespaceAfter(ItemInterface $item, $whitespaceAfter, bool $forceWhitespace)
    {
        $lastToken = $item->lastToken();
        if ($lastToken->isWhitespace()) {
            $this->replaceWithIfNeeded($lastToken, $whitespaceAfter);
        } elseif ($forceWhitespace && $whitespaceAfter !== null) {
            $lastToken->insertAfter(new ContextualToken($whitespaceAfter));
        }
    }

    private function replaceWithIfNeeded(ContextualToken $token, string $replacement = null)
    {
        if ($replacement === null) {
            $token->previousToken()->setNextContextualToken($token->getNextToken());
            return;
        }

        if ($this->hasExtraLinesWithCorrectEnding($token->getContent(), $replacement)) {
            return;
        }

        $token->replaceWith(new ContextualToken($replacement));
    }

    private function hasExtraLinesWithCorrectEnding(string $current, string $replacement): bool
    {
        return (
            substr($replacement, 0, 1) === "\n"
            && substr($current, 0, 1) === "\n"
            && substr($current, -strlen($replacement)) === $replacement
        );
    }
}
