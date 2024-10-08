<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use SplFileInfo;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;

final class DocBlockWhitespaceFixer extends AbstractFixer
{
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'We use only 1 whitespace inside method\'s doc block annotations.',
            [
                new CodeSample(
                    <<<'PHP'
<?php

namespace Some\Service;

class Sample
{
    /**
     * @var      $value
     */
    public function sampleFunction($value)
    {
    }
}

PHP,
                ),
            ],
        );
    }

    public function getName(): string
    {
        return 'Paysera/php_basic_code_style_doc_block_whitespace';
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        foreach ($tokens as $index => $token) {
            if ($token->isGivenKind(T_DOC_COMMENT)) {
                Preg::matchAll("/[^\n\r]+[\r\n]*/", $token->getContent(), $matches);
                $lines = $matches[0];

                foreach ($lines as $key => $line) {
                    $matches = preg_split('/(?=\*)/', $line);

                    if (isset($matches[1]) && strpos($matches[1], '@') !== false) {
                        $matches[1] = preg_replace(
                            '/\s{2,}/',
                            ' ',
                            $matches[1],
                        );

                        $lines[$key] = implode($matches);
                    }
                }

                $tokens[$index] = new Token([$token->getId(), implode($lines)]);
            }
        }
    }
}
