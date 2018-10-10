<?php

namespace Paysera\PhpCsFixerConfig\Composer;

use Symfony\Component\Filesystem\Filesystem;

class PhpCsFixerConfigProvider
{
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
