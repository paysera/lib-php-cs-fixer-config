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
            __DIR__ . '/../../defaults/php-cs-fixer.php',
            'php-cs-fixer.php',
        );
        $fileSystem->copy(
            __DIR__ . '/../../defaults/php-cs-fixer-risky.php',
            'php-cs-fixer-risky.php',
        );
        $fileSystem->copy(
            __DIR__ . '/../../defaults/php-cs-fixer-safe.php',
            'php-cs-fixer-safe.php',
        );
    }
}
