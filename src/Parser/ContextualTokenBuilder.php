<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\ItemInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

class ContextualTokenBuilder
{
    /**
     * @param Token[]|Tokens $tokens
     * @return ContextualToken
     */
    public function buildFromTokens($tokens): ContextualToken
    {
        /** @var ContextualToken $firstContextualToken */
        $firstContextualToken = null;
        /** @var ContextualToken $previousContextualToken */
        $previousContextualToken = null;
        foreach ($tokens as $key => $token) {
            $contextualToken = new ContextualToken($token->getPrototype());
            if ($previousContextualToken !== null) {
                $previousContextualToken->setNextContextualToken($contextualToken);
            }

            $previousContextualToken = $contextualToken;

            if ($firstContextualToken === null) {
                $firstContextualToken = $contextualToken;
            }
        }

        return $firstContextualToken;
    }

    public function overrideTokens(Tokens $tokens, ContextualToken $firstToken)
    {
        $allTokens = [];
        $token = $firstToken;
        do {
            $allTokens[] = new Token($token->getPrototype());
            $token = $token->getNextToken();
        } while ($token !== null);

        $tokens->overrideRange(0, count($tokens) - 1, $allTokens);
    }

    public function replaceItem(ItemInterface $itemToReplace, ItemInterface $replaceWith)
    {
        $itemToReplace->firstToken()->previousToken()->setNextContextualToken($replaceWith->firstToken());
        $replaceWith->lastToken()->setNextContextualToken($itemToReplace->lastToken()->nextToken());
    }
}
