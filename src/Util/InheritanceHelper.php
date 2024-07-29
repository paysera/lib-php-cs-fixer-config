<?php

declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Util;

use ReflectionException;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionClass;
use ReflectionProperty;

class InheritanceHelper
{
    public function isMethodFromInterface(string $methodName, Tokens $tokens): bool
    {
        try {
            $reflection = $this->getReflection($tokens);
        } catch (ReflectionException $exception) {
            return false;
        }

        if ($reflection !== null) {
            foreach ($reflection->getInterfaces() as $interface) {
                $methods = $interface->getMethods(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
                foreach ($methods as $method) {
                    if ($method->getName() === $methodName) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function isPropertyInherited(string $propertyName, Tokens $tokens): bool
    {
        try {
            $reflection = $this->getReflection($tokens);
        } catch (ReflectionException $exception) {
            return false;
        }

        if ($reflection !== null) {
            while ($parent = $reflection->getParentClass()) {
                $reflection = $parent;
                $properties = $parent->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
                foreach ($properties as $property) {
                    if ($property->getName() === $propertyName) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @throws ReflectionException
     */
    private function getReflection(Tokens $tokens): ?ReflectionClass
    {
        $fqcn = null;

        foreach ($tokens as $key => $token) {
            if ($token->isGivenKind(T_NAMESPACE)) {
                $index = $key + 1;
                $namespace = '';
                while (!$tokens[$index + 1]->equals(';')) {
                    $index++;
                    $namespace .= $tokens[$index]->getContent();
                }
                $fqcn = $namespace;
            }

            if ($token->isGivenKind(T_CLASS)) {
                $classNameIndex = $tokens->getNextNonWhitespace($key);
                $fqcn .= '\\' . $tokens[$classNameIndex]->getContent();
            }
        }

        if ($fqcn === null) {
            return null;
        }

        return new ReflectionClass($fqcn);
    }
}
