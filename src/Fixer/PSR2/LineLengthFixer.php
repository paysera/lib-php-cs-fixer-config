<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PSR2;

use Paysera\PhpCsFixerConfig\Parser\ContextualTokenBuilder;
use Paysera\PhpCsFixerConfig\Parser\Entity\ContextualToken;
use Paysera\PhpCsFixerConfig\Parser\Entity\EmptyToken;
use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class LineLengthFixer extends AbstractFixer implements ConfigurableFixerInterface
{
    use ConfigurableFixerTrait {
        configure as public configureConfigurableFixerTrait;
    }

    public const DEFAULT_SOFT_LIMIT = 120;
    public const DEFAULT_HARD_LIMIT = 80;
    private int $softLimit;
    private int $hardLimit;

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Checks all lines in the file, and throws warnings if they are over hard and soft limits.',
            [
                new CodeSample(
                    <<<'PHP'
<?php 
echo "something"."something"."something"."something"."something"."something"."something"."something"."some"."until here ->";

PHP,
                ),
                new CodeSample(
                    <<<'PHP'
<?php 
echo "something"."something"."something"."something"."some"."until here ->";

PHP
                    ,
                    [
                        'limits' => [
                            'soft_limit' => 60,
                            'hard_limit' => 40,
                        ],
                    ],
                ),
            ],
            null,
            'Paysera recommendation.'
        );
    }

    public function getName(): string
    {
        return 'Paysera/psr_2_line_length';
    }

    public function isRisky(): bool
    {
        // Paysera Recommendation
        return true;
    }

    public function getPriority(): int
    {
        // Adding comments to the end of file / Should be last fixer to run
        return -50;
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_OPEN_TAG);
    }

    public function configure(array $configuration = null): void
    {
        $this->configureConfigurableFixerTrait($configuration);

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

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $contextualTokenBuilder = new ContextualTokenBuilder();
        $token = $contextualTokenBuilder->buildFromTokens($tokens);
        $firstToken = (new EmptyToken())->setNextContextualToken($token);

        $maxTokenLength = 0;
        $currentLineLength = 0;
        $firstLineToken = $token;
        $previousMatches = null;

        while ($token !== null) {
            if (preg_match('/^([^\n]*)(.*?\n.*?)([^\n]*)$/', $token->getContent(), $matches) === 1) {
                $currentLineLength += mb_strlen($matches[1]);
                $this->handleLineEnd($firstLineToken, $currentLineLength, $maxTokenLength, $previousMatches);

                $firstLineToken = $token;
                $currentLineLength = $maxTokenLength = mb_strlen($matches[3]);
                $previousMatches = $matches;
            } else {
                $tokenLength = mb_strlen($token->getContent());
                $currentLineLength += $tokenLength;
                if ($tokenLength > $maxTokenLength) {
                    $maxTokenLength = $tokenLength;
                }
            }

            $token = $token->getNextToken();
        }

        $contextualTokenBuilder->overrideTokens($tokens, $firstToken);
    }

    private function handleLineEnd(
        ContextualToken $firstToken,
        int $lineLength,
        int $maxTokenLength,
        array $newLineParts = null
    ) {
        if ($lineLength <= $this->hardLimit || $newLineParts === null) {
            return;
        }

        $comment = sprintf('// todo: following line exceeds %s characters', $this->hardLimit);

        if ($firstToken->previousToken()->getContent() === $comment) {
            return;
        }

        if ($maxTokenLength + mb_strlen($newLineParts[3]) > 0.8 * $this->hardLimit) {
            return;
        }

        $contents = [];
        if ($newLineParts[1] !== '') {
            $contents[] = new ContextualToken($newLineParts[1]);
        }
        $contents[] = new ContextualToken($newLineParts[2] . $newLineParts[3]);
        $contents[] = new ContextualToken(
            [T_COMMENT, $comment],
        );
        $contents[] = new ContextualToken("\n" . $newLineParts[3]);

        $firstToken->replaceWithTokens($contents);
    }

    public function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('limits', 'Set hard and soft limits of line length, e.g. `["soft_limit" => 120, "hard_limit" => 80]`.'))
                ->setAllowedTypes(['array', 'bool'])
                ->setDefault([
                    'soft_limit' => self::DEFAULT_SOFT_LIMIT,
                    'hard_limit' => self::DEFAULT_HARD_LIMIT,
                ])
                ->getOption(),
        ])
            ;
    }
}
