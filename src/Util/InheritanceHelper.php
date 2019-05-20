<?php
declare(strict_types=1);

namespace Paysera\PhpCsFixerConfig\Util;

use ReflectionException;
use PhpCsFixer\Tokenizer\Tokens;
use ReflectionClass;
use ReflectionProperty;

class InheritanceHelper
{
    /**
     * @param string $methodName
     * @param Tokens $tokens
     * @return bool
     */
    public function isMethodFromInterface($methodName, Tokens $tokens)
    {
        try {
            $reflection = $this->getReflection($tokens);
        } catch (ReflectionException $exception) {
            return false;
        }

        foreach ($reflection->getInterfaces() as $interface) {
            $methods = $interface->getMethods(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
            foreach ($methods as $method) {
                if ($method->getName() === $methodName) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string $propertyName
     * @param Tokens $tokens
     * @return bool
     */
    public function isPropertyInherited($propertyName, Tokens $tokens)
    {
        try {
            $reflection = $this->getReflection($tokens);
        } catch (ReflectionException $exception) {
            return false;
        }
        $parents = [];

        while ($parent = $reflection->getParentClass()) {
            $parents[] = $parent->getName();
            $reflection = $parent;
            $properties = $parent->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
            foreach ($properties as $property) {
                if ($property->getName() === $propertyName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getReflection(Tokens $tokens)
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
            return false;
        }

        return new ReflectionClass($fqcn);
    }
}
