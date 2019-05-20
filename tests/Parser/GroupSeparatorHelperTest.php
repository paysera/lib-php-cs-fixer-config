<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Tests\Parser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\GroupSeparatorHelper;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use Paysera\PhpCsFixerConfig\Parser\Entity\SeparatedItemList;
use PHPUnit\Framework\TestCase;

class GroupSeparatorHelperTest extends TestCase
{

    /**
     * @param ItemInterface|null $expected
     * @param ItemInterface[] $itemList
     * @param string $separator
     *
     * @dataProvider provider
     */
    public function testRegroupListBySeparator(ItemInterface $expected = null, array $itemList, string $separator)
    {
        $helper = new GroupSeparatorHelper();

        $result = $helper->regroupListBySeparator($itemList, $separator);

        $this->assertEquals($expected, $result);
    }

    public function provider()
    {
        $list1 = [' ', '$a', ' ', '&&', ' ', '$b', ' ', '||', ' ', '$c', ' '];
        $tokenList1 = $this->mapToTokens($list1);

        return [
            implode('', $list1) => [
                (new SeparatedItemList('||'))
                    ->addPrefixWhitespaceItem(new ContextualToken(' '))
                    ->addContentItemGroup([
                        new ContextualToken('$a'),
                        new ContextualToken(' '),
                        new ContextualToken('&&'),
                        new ContextualToken(' '),
                        new ContextualToken('$b'),
                    ])
                    ->addSeparatorGroup([
                        new ContextualToken(' '),
                        new ContextualToken('||'),
                        new ContextualToken(' '),
                    ])
                    ->addContentItemGroup([new ContextualToken('$c')])
                    ->addPostfixWhitespaceItem(new ContextualToken(' '))
                ,
                $tokenList1,
                '||',
            ],
            'same as given if no such separator' => [
                null,
                $tokenList1,
                '>',
            ],
            'Separated with comment' => [
                (new SeparatedItemList('&&'))
                    ->addContentItemGroup([new ContextualToken('$a')])
                    ->addSeparatorGroup([
                        new ContextualToken(' '),
                        new ContextualToken('&&'),
                        new ContextualToken(' '),
                        new ContextualToken([T_COMMENT, '/* comment */']),
                        new ContextualToken(' '),
                    ])
                    ->addContentItemGroup([new ContextualToken('$b')])
                ,
                $this->mapToTokens(['$a', ' ', '&&', ' ', [T_COMMENT, '/* comment */'], ' ', '$b']),
                '&&'
            ],
        ];
    }

    private function mapToTokens(array $list): array
    {
        return array_map(function($content) {
            return new ContextualToken($content);
        }, $list);
    }
}
