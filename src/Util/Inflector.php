<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Util;

use Doctrine\Common\Inflector\Inflector as DoctrineInflectorV1;
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
            return new DoctrineInflectorV1();
        }

        throw new RuntimeException('Doctrine Inflector is not available');
    }

    public function pluralize(string $word): string
    {
        if (method_exists($this->inflector, 'pluralize')) {
            // Doctrine Inflector v2
            return $this->inflector->pluralize($word);
        }

        // Doctrine Inflector v1
        return DoctrineInflectorV1::pluralize($word);
    }

    public function singularize(string $word): string
    {
        if (method_exists($this->inflector, 'singularize')) {
            // Doctrine Inflector v2
            return $this->inflector->singularize($word);
        }

        // Doctrine Inflector v1
        return DoctrineInflectorV1::singularize($word);
    }
}
