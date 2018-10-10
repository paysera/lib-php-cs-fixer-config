<?php
declare(strict_types=1);

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    include $autoload;
}

return Paysera\PhpCsFixerConfig\Config\PayseraConventionsConfig::create()
    ->setDefaultFinder(['src'], ['tests', 'Tests', 'test', 'Test'])
    ->setRecommendedRules()
;
