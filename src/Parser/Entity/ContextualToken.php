<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Parser\Entity;

use Paysera\PhpCsFixerConfig\Parser\Exception\NoMoreTokensException;
use RuntimeException;
use PhpCsFixer\Tokenizer\Token;

class ContextualToken implements ItemInterface
{
    private Token $token;
    private ?ContextualToken $previousContextualToken = null;
    private ?ContextualToken $nextContextualToken = null;

    public function __construct($tokenAbstract)
    {
        if (is_string($tokenAbstract) && trim($tokenAbstract, " \t\n\r\0\x0B") === '' && $tokenAbstract !== '') {
            $this->token = new Token([T_WHITESPACE, $tokenAbstract]);
        } else {
            $this->token = new Token($tokenAbstract);
        }
    }

    /**
     * @return $this
     */
    public function setNextContextualToken(ContextualToken $contextualToken): self
    {
        $this->nextContextualToken = $contextualToken;
        $contextualToken->previousContextualToken = $this;

        return $this;
    }

    public function setContent($tokenAbstract)
    {
        $this->token = new Token($tokenAbstract);
    }

    public function replaceWith(ContextualToken $contextualToken)
    {
        if ($this->previousContextualToken === null) {
            throw new RuntimeException('Cannot replace first contextual token');
        }

        $this->previousToken()->setNextContextualToken($contextualToken);
        if ($this->nextContextualToken !== null) {
            $contextualToken->setNextContextualToken($this->nextContextualToken);
        }
    }

    public function insertBefore(ContextualToken $contextualToken)
    {
        if ($this->previousContextualToken === null) {
            throw new RuntimeException('Cannot insert before first contextual token');
        }

        $this->previousToken()->setNextContextualToken($contextualToken);
        $contextualToken->setNextContextualToken($this);
    }

    public function insertAfter(ContextualToken $contextualToken)
    {
        if ($this->nextContextualToken !== null) {
            $contextualToken->setNextContextualToken($this->nextContextualToken);
        }
        $this->setNextContextualToken($contextualToken);
    }

    public function getNextToken(): ?ContextualToken
    {
        return $this->nextContextualToken;
    }

    public function nextToken(): ?ContextualToken
    {
        if ($this->nextContextualToken === null) {
            throw new NoMoreTokensException('No more tokens');
        }

        return $this->nextContextualToken;
    }

    public function nextNonWhitespaceToken(): ContextualToken
    {
        $nextToken = $this->nextToken();
        while ($nextToken->isWhitespace()) {
            $nextToken = $nextToken->nextToken();
        }

        return $nextToken;
    }

    public function nextTokenWithContent(string $content): ContextualToken
    {
        $nextToken = $this->nextToken();
        while ($nextToken->getContent() !== $content) {
            $nextToken = $nextToken->nextToken();
        }

        return $nextToken;
    }

    public function previousToken(): ?ContextualToken
    {
        if ($this->previousContextualToken === null) {
            throw new RuntimeException('No more tokens');
        }

        return $this->previousContextualToken;
    }

    public function previousNonWhitespaceToken(): ContextualToken
    {
        $previousToken = $this->previousToken();
        while ($previousToken->getToken()->isWhitespace()) {
            $previousToken = $previousToken->previousToken();
        }

        return $previousToken;
    }

    public function lastToken(): ContextualToken
    {
        return $this;
    }

    public function firstToken(): ContextualToken
    {
        return $this;
    }

    public function getComplexItemLists(): array
    {
        return [];
    }

    public function isSplitIntoSeveralLines(): bool
    {
        return strpos($this->getContent(), "\n") !== false;
    }

    public function getLineIndent(): string
    {
        $codeBefore = '';
        $token = $this;
        while (($token = $token->previousContextualToken) !== null) {
            $codeBefore = $token->getContent() . $codeBefore;
            $newLinePosition = strrpos($codeBefore, "\n");
            if ($newLinePosition !== false) {
                $codeBefore = substr($codeBefore, $newLinePosition + 1);
                break;
            }
        }
        if (preg_match('/^([\s\t]*)/', $codeBefore, $matches) !== 1) {
            throw new RuntimeException('Expected regexp to always match when searching for line indent');
        }

        return $matches[1];
    }

    /**
     * @param ContextualToken $item
     */
    public function equalsToItem(ItemInterface $item): bool
    {
        return $item instanceof $this && $this instanceof $item && $this->token->equals($item->getToken());
    }

    /**
     * @param array|Token[] $tokens
     */
    public function replaceWithTokens(array $tokens)
    {
        $lastToken = $this->previousToken();
        foreach ($tokens as $token) {
            $lastToken->setNextContextualToken($token);
            $lastToken = $token;
        }
        $lastToken->setNextContextualToken($this->nextToken());
    }

    public function insertSequenceBefore(array $tokens)
    {
        $insertAfterThis = $this->previousToken();
        foreach ($tokens as $token) {
            $insertAfterThis->insertAfter($token);
            $insertAfterThis = $token;
        }
    }

    public function getContent(): string
    {
        return $this->token->getContent();
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function isGivenKind($possibleKind): bool
    {
        return $this->token->isGivenKind($possibleKind);
    }

    public function getPrototype()
    {
        return $this->token->getPrototype();
    }

    public function isWhitespace(?string $whitespaces = " \t\n\r\0\x0B"): bool
    {
        return $this->token->isWhitespace($whitespaces);
    }

    public function isComment(): bool
    {
        return $this->token->isComment();
    }
}
