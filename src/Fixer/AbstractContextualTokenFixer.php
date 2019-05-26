<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer;

use Paysera\PhpCsFixerConfig\Parser\ContextualTokenBuilder;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\EmptyToken;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

/**
 * @php-cs-fixer-ignore Paysera/php_basic_feature_visibility_properties
 */
abstract class AbstractContextualTokenFixer extends AbstractFixer
{
    protected $contextualTokenBuilder;

    public function __construct()
    {
        parent::__construct();

        $this->contextualTokenBuilder = new ContextualTokenBuilder();
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        $token = $this->contextualTokenBuilder->buildFromTokens($tokens);
        $firstToken = (new EmptyToken())->setNextContextualToken($token);

        $this->applyFixOnContextualToken($token);

        $this->contextualTokenBuilder->overrideTokens($tokens, $firstToken);
    }

    abstract protected function applyFixOnContextualToken(ContextualToken $token);
}
