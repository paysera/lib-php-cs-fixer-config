<?php

namespace Paysera\PhpCsFixerConfig\Fixer\PSR2;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverRootless;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

final class LineLengthFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface
{
    const DEFAULT_SOFT_LIMIT = 120;
    const DEFAULT_HARD_LIMIT = 80;

    /**
     * @var int
     */
    private $softLimit;

    /**
     * @var int
     */
    private $hardLimit;

    public function getDefinition()
    {
        return new FixerDefinition(
            'Checks all lines in the file, and throws warnings if they are over hard and soft limits.',
            [
                new CodeSample('
                <?php 
                    echo "something"."something"."something"."something"."something"."something"."some"."until here ->";
                '),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/psr_2_line_length';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function getPriority()
    {
        // Adding comments to the end of file / Should be last fixer to run
        return -50;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_OPEN_TAG);
    }

    public function configure(array $configuration = null)
    {
        parent::configure($configuration);

        if ($this->configuration['limits'] === true) {
            $this->softLimit = self::DEFAULT_SOFT_LIMIT;
            $this->hardLimit = self::DEFAULT_HARD_LIMIT;
        }

        if (isset($this->configuration['limits']['soft_limit'])) {
            $this->softLimit = $this->configuration['limits']['soft_limit'];
        }

        if (isset($this->configuration['limits']['hard_limit'])) {
            $this->hardLimit = $this->configuration['limits']['hard_limit'];
        }
    }

    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $openedFile = $file->openFile();
        $lineNumber = 0;
        while (!$openedFile->eof()) {
            $line = preg_replace('#\n#', '', $openedFile->getCurrentLine());
            $lineNumber++;
            if (isset($this->softLimit) && strlen($line) > $this->softLimit
                && !$this->isCommentFound($tokens, $lineNumber)
            ) {
                $this->addResult(
                    $tokens,
                    $lineNumber,
                    strlen($line),
                    $this->softLimit,
                    'soft_limit'
                );
            } elseif (isset($this->hardLimit) && strlen($line) > $this->hardLimit
                && !$this->isCommentFound($tokens, $lineNumber)
            ) {
                $this->addResult(
                    $tokens,
                    $lineNumber,
                    strlen($line),
                    $this->hardLimit,
                    'hard_limit'
                );
            }
        }
    }

    protected function createConfigurationDefinition()
    {
        $limits = new FixerOptionBuilder(
            'limits',
            'Set hard and soft limits of line length, e.g. `["soft_limit" => 120, "hard_limit" => 80]`.'
        );

        $limits = $limits
            ->setAllowedTypes(['array', 'bool'])
            ->getOption()
        ;

        return new FixerConfigurationResolverRootless('limits', [$limits]);
    }

    /**
     * @param Tokens $tokens
     * @param int $currentLineNumber
     * @return bool
     */
    private function isCommentFound(Tokens $tokens, $currentLineNumber)
    {
        // Find if comment is already added
        foreach ($tokens as $token) {
            if ($token->isGivenKind(T_COMMENT)
                && preg_match("#\/\/\sTODO:\sLine\s\(" . $currentLineNumber . "\)#", $token->getContent()) === 1
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Tokens $tokens
     * @param int $lineNumber
     * @param int $lineLength
     * @param int $limit
     * @param string $limitName
     */
    private function addResult(Tokens $tokens, $lineNumber, $lineLength, $limit, $limitName)
    {
        $comment = '// TODO: Line (' . $lineNumber . ') exceeds ' . strtoupper($limitName) . ' of ' . $limit
            . ' characters; contains ' . $lineLength . ' characters';

        $tokens->insertAt(
            $tokens->count(),
            new Token([
                T_WHITESPACE,
                "\n",
            ])
        );

        $tokens->insertAt(
            $tokens->count(),
            new Token([
                T_COMMENT,
                $comment,
            ])
        );
    }
}
