<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\CodeStyle;

use SplFileInfo;
use PhpCsFixer\Utils;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;

final class DocBlockWhitespaceFixer extends AbstractFixer
{
    public function getDefinition()
    {
        return new FixerDefinition(
            'We use only 1 whitespace inside method\'s doc block annotations',
            [
                new CodeSample('
                <?php
                
                namespace Some\Namespace;
                
                class Sample
                {
                    /**
                     * @var      $value
                     */
                    public function sampleFunction($value)
                    {
                    }
                }
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_code_style_doc_block_whitespace';
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $token) {
            if ($token->isGivenKind(T_DOC_COMMENT)) {
                $lines = Utils::splitLines($token->getContent());

                foreach ($lines as $key => $line) {
                    $matches = preg_split('/(?=\*)/', $line);

                    if (isset($matches[1])) {
                        $matches[1] = preg_replace(
                            '/\s{2,}/',
                            ' ',
                            $matches[1]
                        );

                        $lines[$key] = implode($matches);
                    }
                }

                $token->setContent(implode($lines));
            }
        }
    }
}
