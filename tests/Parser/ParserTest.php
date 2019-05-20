<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Parser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ComplexItemList;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\ContextualTokenBuilder;
use Paysera\PhpCsFixerConfig\Parser\Entity\EmptyToken;
use Paysera\PhpCsFixerConfig\Parser\GroupSeparatorHelper;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use Paysera\PhpCsFixerConfig\Parser\Parser;
use Paysera\PhpCsFixerConfig\Parser\Entity\SeparatedItemList;
use Paysera\PhpCsFixerConfig\Parser\Entity\SimpleItemList;
use Paysera\PhpCsFixerConfig\Parser\Entity\TypedItem;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{

    /**
     * @param ItemInterface $expected
     * @param ItemInterface $prefixItem
     * @param string $endTokenValue
     *
     * @dataProvider provider
     */
    public function testParseUntil(ItemInterface $expected, ItemInterface $prefixItem, string $endTokenValue)
    {
        $helper = new Parser(new GroupSeparatorHelper());

        $result = $helper->parseUntil($prefixItem, $endTokenValue);

        $this->assertTokensEqual($expected, $result);
    }

    public function provider()
    {
        yield 'Single item' => [
            (new ComplexItemList())
                ->addPrefixItem(new ContextualToken('('))
                ->addContentItemGroup([
                    (new ComplexItemList())
                        ->addPrefixWhitespaceItem(new ContextualToken("\n    "))
                        ->addContentItem(new ContextualToken('$a'))
                        ->addPostfixWhitespaceItem(new ContextualToken("\n"))
                    ,
                ])
                ->addPostfixItem(new ContextualToken(')'))
            ,
            $this->mapToTokens(['(', "\n    ", '$a', "\n", ')']),
            ')',
        ];

        yield 'Separated' => [
            (new ComplexItemList())
                ->addPrefixItem(new ContextualToken('('))
                ->addContentItemGroup([
                    (new SeparatedItemList('&&'))
                        ->addPrefixWhitespaceItem(new ContextualToken("\n    "))
                        ->addContentItem(new ContextualToken('$a'))
                        ->addSeparatorGroup([
                            new ContextualToken("\n    "),
                            new ContextualToken('&&'),
                            new ContextualToken(' '),
                        ])
                        ->addContentItem(new ContextualToken('$b'))
                        ->addPostfixWhitespaceItem(new ContextualToken("\n"))
                    ,
                ])
                ->addPostfixItem(new ContextualToken(')'))
            ,
            $this->mapToTokens(['(', "\n    ", '$a', "\n    ", '&&', ' ', '$b', "\n", ')']),
            ')',
        ];

        $tokens = ['(', "\n    ", '$a', "\n    ", '&&', ' ', '$b', "\n", '||', '$c', "\n", ')'];
        yield 'Multiple levels' => [
            (new ComplexItemList())
                ->addPrefixItem(new ContextualToken('('))
                ->addContentItemGroup([
                    (new SeparatedItemList('||'))
                        ->addPrefixWhitespaceItem(new ContextualToken("\n    "))
                        ->addContentItem(
                            (new ComplexItemList())
                                ->addPrefixItem(new EmptyToken())
                                ->addContentItem(
                                    (new SeparatedItemList('&&'))
                                        ->addContentItem(new ContextualToken('$a'))
                                        ->addSeparatorGroup([
                                            new ContextualToken("\n    "),
                                            new ContextualToken('&&'),
                                            new ContextualToken(' '),
                                        ])
                                        ->addContentItem(new ContextualToken('$b'))
                                )
                                ->addPostfixItem(new EmptyToken())
                        )
                        ->addSeparatorGroup([
                            new ContextualToken("\n"),
                            new ContextualToken('||'),
                        ])
                        ->addContentItem(new ContextualToken('$c'))
                        ->addPostfixWhitespaceItem(new ContextualToken("\n"))
                    ,
                ])
                ->addPostfixItem(new ContextualToken(')'))
            ,
            $this->mapToTokens($tokens),
            ')',
        ];

        yield 'Comma separated' => [
            (new ComplexItemList())
                ->addPrefixItem(new ContextualToken('['))
                ->addContentItemGroup([
                    (new SeparatedItemList(','))
                        ->addPrefixWhitespaceItem(new ContextualToken("\n    "))
                        ->addContentItem(new ContextualToken('$a'))
                        ->addSeparatorGroup([
                            new ContextualToken(','),
                            new ContextualToken("\n    "),
                        ])
                        ->addContentItem(new ContextualToken('$b'))
                        ->addSeparatorGroup([
                            new ContextualToken(','),
                        ], TypedItem::TYPE_SEPARATOR_AFTER_CONTENTS)
                        ->addPostfixWhitespaceItem(new ContextualToken("\n"))
                    ,
                ])
                ->addPostfixItem(new ContextualToken(']'))
            ,
            $this->mapToTokens(['[', "\n    ", '$a', ',', "\n    ", '$b', ',', "\n", ']']),
            ']',
        ];

        yield 'Simple comment' => [
            (new ComplexItemList())
                ->addPrefixItem(new ContextualToken('('))
                ->addContentItemGroup([
                    (new ComplexItemList())
                        ->addPrefixWhitespaceItem(new ContextualToken("\n    "))
                        ->addContentItem(new SimpleItemList([
                            new ContextualToken('$a'),
                            new ContextualToken(' '),
                            new ContextualToken([T_COMMENT, '// comment']),
                        ]))
                        ->addPostfixWhitespaceItem(new ContextualToken("\n"))
                    ,
                ])
                ->addPostfixItem(new ContextualToken(')'))
            ,
            $this->mapToTokens(['(', "\n    ", '$a', ' ', [T_COMMENT, '// comment'], "\n", ')']),
            ')',
        ];

        $tokens = ['[', "\n    ", '$a', ' ', ',', ' ', [T_COMMENT, '// comment'], "\n    ", '$b', ',', "\n", ']'];
        yield 'Comma separated with comment' => [
            (new ComplexItemList())
                ->addPrefixItem(new ContextualToken('['))
                ->addContentItemGroup([
                    (new SeparatedItemList(','))
                        ->addPrefixWhitespaceItem(new ContextualToken("\n    "))
                        ->addContentItem(new ContextualToken('$a'))
                        ->addSeparatorGroup([
                            new ContextualToken(' '),
                            new ContextualToken(','),
                            new ContextualToken(' '),
                            new ContextualToken([T_COMMENT, '// comment']),
                            new ContextualToken("\n    "),
                        ])
                        ->addContentItem(new ContextualToken('$b'))
                        ->addSeparatorGroup([
                            new ContextualToken(','),
                        ], TypedItem::TYPE_SEPARATOR_AFTER_CONTENTS)
                        ->addPostfixWhitespaceItem(new ContextualToken("\n"))
                    ,
                ])
                ->addPostfixItem(new ContextualToken(']'))
            ,
            $this->mapToTokens($tokens),
            ']',
        ];
    }

    private function mapToTokens(array $list): ContextualToken
    {
        $builder = new ContextualTokenBuilder();
        return $builder->buildFromTokens(
            array_map(function($content) {
                return new ContextualToken($content);
            }, $list)
        );
    }

    private function assertTokensEqual(ItemInterface $expected, ItemInterface $result)
    {
        if (!$expected->equalsToItem($result)) {
            // if not equals - compare explicitly to see the diff
            $this->assertEquals($expected, $result);
        }

        $this->addToAssertionCount(1);
    }
}
