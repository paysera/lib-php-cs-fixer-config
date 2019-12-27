<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Fixer\PhpBasic\Feature;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class DateTimeFixer extends AbstractFixer
{
    const CONVENTION = 'PhpBasic convention 3.19: Use \DateTime object instead';

    private $dateFunctions;

    public function __construct()
    {
        parent::__construct();
        $this->dateFunctions = [
            'date_add',
            'date_create_from_format',
            'date_create',
            'date_date_set',
            'date_diff',
            'date_format',
            'date_get_last_errors',
            'date_interval_create_from_date_string',
            'date_interval_format',
            'date_isodate_set',
            'date_modify',
            'date_offset_get',
            'date_parse_from_format',
            'date_parse',
            'date_sub',
            'date_time_set',
            'date_timestamp_get',
            'date_timestamp_set',
            'date_timezone_get',
            'date_timezone_set',
            'date',
            'getdate',
            'gettimeofday',
            'gmdate',
            'gmmktime',
            'gmstrftime',
            'idate',
            'localtime',
            'mktime',
            'strftime',
            'strptime',
            'strtotime',
            'time',
            'timezone_abbreviations_list',
            'timezone_identifiers_list',
            'timezone_location_get',
            'timezone_name_from_abbr',
            'timezone_name_get',
            'timezone_offset_get',
            'timezone_open',
            'timezone_transitions_get',
        ];
    }

    public function getDefinition()
    {
        return new FixerDefinition(
            'We use \DateTime object to represent date or date and time inside system.',
            [
                new CodeSample(
                    '<?php
                        class Sample
                        {
                            private function sampleFunction()
                            {
                                date_default_timezone_set(\'UTC\');
                                $someDate = date("l");
                                $otherDate = date(\'l jS \of F Y h:i:s A\');
                            }
                        }
                    '
                ),
            ]
        );
    }

    public function getName()
    {
        return 'Paysera/php_basic_feature_date_time';
    }

    public function isRisky()
    {
        // Paysera Recommendation
        return true;
    }

    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_STRING);
    }

    public function applyFix(SplFileInfo $file, Tokens $tokens)
    {
        foreach ($tokens as $key => $token) {
            if (!$token->isGivenKind(T_STRING)) {
                continue;
            }

            $parenthesesIndex = $tokens->getNextMeaningfulToken($key);
            if ($parenthesesIndex === null || !$tokens[$parenthesesIndex]->equals('(')) {
                continue;
            }

            if (in_array($token->getContent(), $this->dateFunctions, true)) {
                $endOfLineIndex = $tokens->getNextTokenOfKind($key, ['{', ';']);
                $commentIndex = $tokens->getNextNonWhitespace($endOfLineIndex);
                if ($commentIndex === null || !$tokens[$commentIndex]->isGivenKind(T_COMMENT)) {
                    $this->addResult($tokens, $key, $endOfLineIndex);
                }
            }
        }
    }

    /**
     * @param Tokens $tokens
     * @param int $functionIndex
     * @param int $endOfLineIndex
     */
    private function addResult(Tokens $tokens, $functionIndex, $endOfLineIndex)
    {
        $tokens->insertAt(
            $endOfLineIndex + 1,
            new Token([T_COMMENT, '// TODO: "' . $tokens[$functionIndex]->getContent() . '" - ' . self::CONVENTION])
        );
        $tokens->insertAt($endOfLineIndex + 1, new Token([T_WHITESPACE, ' ']));
    }
}
