<?php

namespace Paysera\PhpCsFixerConfig\Parser;

use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use PhpCsFixer\Tokenizer\Token;

class ContextualTokenBuilder
{
    /**
     * @param Token[] $tokens
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
}
