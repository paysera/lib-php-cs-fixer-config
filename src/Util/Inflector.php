<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Util;

use Doctrine\Inflector\InflectorFactory;
use RuntimeException;

class Inflector
{
    private $inflector;
    public function __construct()
    {
        $this->inflector = $this->getInflector();
    }

    private function getInflector()
    {
        if (class_exists('Doctrine\Inflector\InflectorFactory')) {
            // Doctrine Inflector v2
            return InflectorFactory::create()->build();
        }

        if (class_exists('Doctrine\Common\Inflector\Inflector')) {
            // Doctrine Inflector v1
            return new \Doctrine\Common\Inflector\Inflector();
        }

        throw new RuntimeException('Doctrine Inflector is not available');
    }

    public function pluralize($word)
    {
        if (method_exists($this->inflector, 'pluralize')) {
            // Doctrine Inflector v2
            return $this->inflector->pluralize($word);
        }

        // Doctrine Inflector v1
        return \Doctrine\Common\Inflector\Inflector::pluralize($word);
    }

    public function singularize($word)
    {
        if (method_exists($this->inflector, 'singularize')) {
            // Doctrine Inflector v2
            return $this->inflector->singularize($word);
        }

        // Doctrine Inflector v1
        return \Doctrine\Common\Inflector\Inflector::singularize($word);
    }
}
