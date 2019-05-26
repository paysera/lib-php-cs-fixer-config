<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Composer;

use Symfony\Component\Filesystem\Filesystem;

/**
 * @php-cs-fixer-ignore Paysera/php_basic_feature_static_methods
 */
class PhpCsFixerConfigProvider
{
    /**
     * @api
     */
    public static function copyPhpCs()
    {
        $fileSystem = new Filesystem();
        $fileSystem->copy(
            __DIR__ . '/../../defaults/.php_cs',
            '.php_cs',
            false
        );
        $fileSystem->copy(
            __DIR__ . '/../../defaults/.php_cs_risky',
            '.php_cs_risky',
            false
        );
        $fileSystem->copy(
            __DIR__ . '/../../defaults/.php_cs_safe',
            '.php_cs_safe',
            false
        );
    }
}
