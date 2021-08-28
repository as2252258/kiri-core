<?php

namespace Kiri\Di;

use JetBrains\PhpStorm\Pure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

trait Attributes
{


    private array $_classTarget = [];
    private array $_classMethodNote = [];
    private array $_classMethod = [];
    private array $_classPropertyNote = [];
    private array $_classProperty = [];


    /**
     * @param ReflectionClass $class
     */
    protected function setTargetNote(ReflectionClass $class)
    {
        $className = $class->getName();
        if (!isset($this->_classTarget[$className])) {
            $this->_classTarget[$className] = [];
        }
        foreach ($class->getAttributes() as $attribute) {
            if (!class_exists($attribute->getName())) {
                continue;
            }

            $instance = $this->format_annotation($attribute);

            $this->_classTarget[$className][] = $instance;

            $this->setMappingClass($attribute, $className);
        }
    }


    /**
     * @param ReflectionAttribute $attribute
     * @param string $class
     */
    private function setMappingClass(ReflectionAttribute $attribute, string $class)
    {
        if (!isset($this->_mapping[$attribute->getName()])) {
            $this->_mapping[$attribute->getName()] = [];
        }
        if (!isset($this->_mapping[$attribute->getName()][$class])) {
            $this->_mapping[$attribute->getName()][$class] = [];
        }
    }


    /**
     * @param ReflectionAttribute $attribute
     * @param string $class
     * @param string $method
     * @param mixed $instance
     */
    private function setMappingMethod(ReflectionAttribute $attribute, string $class, string $method, mixed $instance)
    {
        $this->setMappingClass($attribute, $class);

        if (!isset($this->_mapping[$attribute->getName()][$class]['method'])) {
            $this->_mapping[$attribute->getName()][$class]['method'] = [];
        }
        $this->_mapping[$attribute->getName()][$class]['method'][] = [$method => $instance];
    }


    /**
     * @param ReflectionAttribute $attribute
     * @param string $class
     * @param string $property
     * @param $instance
     */
    private function setMappingProperty(ReflectionAttribute $attribute, string $class, string $property, $instance)
    {
        $this->setMappingClass($attribute, $class);

        $mapping = $this->_mapping[$attribute->getName()][$class];
        if (!isset($mapping['property'])) {
            $mapping['property'] = [];
        }
        $mapping['property'][] = [$property => $instance];
        $this->_mapping[$attribute->getName()][$class] = $mapping;
    }


    /**
     * @param mixed $class
     * @return array
     */
    public function getTargetNote(mixed $class): array
    {
        if (!is_string($class)) {
            $class = $class::class;
        }
        return $this->_classTarget[$class] ?? [];
    }


    /**
     * @param ReflectionClass $class
     */
    protected function setMethodNote(ReflectionClass $class)
    {
        $className = $class->getName();
        $this->_classMethodNote[$className] = $this->_classMethod[$className] = [];
        foreach ($class->getMethods() as $ReflectionMethod) {
            $this->_classMethod[$className][$ReflectionMethod->getName()] = $ReflectionMethod;
            $this->_classMethodNote[$className][$ReflectionMethod->getName()] = [];
            foreach ($ReflectionMethod->getAttributes() as $attribute) {
                if (!class_exists($attribute->getName())) {
                    continue;
                }
                $instance = $this->format_annotation($attribute);

                $this->_classMethodNote[$className][$ReflectionMethod->getName()][] = $instance;

                $this->setMappingMethod($attribute, $className, $ReflectionMethod->getName(), $instance);
            }
        }
    }


    /**
     * @param string $class
     * @param string $method
     * @return bool
     */
    public function hasMethod(string $class, string $method): bool
    {
        return isset($this->_classMethod[$class]) && isset($this->_classMethod[$class][$method]);
    }


    /**
     * @param ReflectionClass $class
     * @return array
     */
    #[Pure] public function getMethodNote(ReflectionClass $class): array
    {
        return $this->_classMethodNote[$class->getName()] ?? [];
    }


    /**
     * @param ReflectionClass $class
     */
    protected function setPropertyNote(ReflectionClass $class)
    {
        $className = $class->getName();
        $this->_classProperty[$className] = $this->_classPropertyNote[$className] = [];
        foreach ($class->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PUBLIC |
            ReflectionProperty::IS_PROTECTED) as $ReflectionMethod) {
            $this->_classProperty[$className][$ReflectionMethod->getName()] = $ReflectionMethod;
            foreach ($ReflectionMethod->getAttributes() as $attribute) {
                if (!class_exists($attribute->getName())) {
                    continue;
                }

                $instance = $this->format_annotation($attribute);

                $this->_classPropertyNote[$className][$ReflectionMethod->getName()] = $instance;

                $this->setMappingProperty($attribute, $className, $ReflectionMethod->getName(), $instance);
            }
        }
    }


    /**
     * @param \ReflectionAttribute $attribute
     * @return array
     * @throws \ReflectionException
     */
    private function format_annotation(ReflectionAttribute $attribute)
    {
        $attr = new ReflectionClass($attribute->getName());

        $argument = $attribute->getArguments();

        $array = ['class' => $attribute->getName(), 'params' => []];
        foreach ($attr->getConstructor()->getParameters() as $key => $parameter) {
            if (isset($argument[$parameter->getName()])) {
                $array['params'][$parameter->getName()] = $argument[$parameter->getName()];
            } else {
                if (!isset($argument[$key])) {
                    $array['params'][$parameter->getName()] = $parameter->getDefaultValue();
                } else {
                    $array['params'][$parameter->getName()] = $argument[$key];
                }
            }
        }
        return $array;
    }


    /**
     * @param string $attribute
     * @param string|null $class
     * @return array[]
     */
    public function getAttributeTrees(string $attribute, string $class = null): array
    {
        $mapping = $this->_mapping[$attribute] ?? [];
        if (empty($mapping) || empty($class)) {
            return $mapping;
        }
        return $mapping[$class] ?? [];
    }


    /**
     * @param string $attribute
     * @param string $class
     * @param string|null $method
     * @return array
     */
    public function getMethodByAnnotation(string $attribute, string $class, string $method = null): mixed
    {
        $class = $this->getAttributeTrees($attribute, $class);
        if (empty($class) || !isset($class['method']) || empty($method)) {
            return $class['method'] ?? [];
        }
        foreach ($class['method'] as $value) {
            $key = key($value);
            if ($method == $key) {
                return $value[$key];
            }
        }
        return null;
    }


    /**
     * @param string $attribute
     * @param string $class
     * @param string $method
     * @return mixed
     */
    public function getPropertyByAnnotation(string $attribute, string $class, string $method): mixed
    {
        $class = $this->getAttributeTrees($attribute, $class);
        if (empty($class) || !isset($class['property'])) {
            return [];
        }
        foreach ($class['property'] as $value) {
            $key = key($value);
            if ($method == $key) {
                return $value[$key];
            }
        }
        return null;
    }


    /**
     * @param ReflectionClass|string $class
     * @return array
     * @throws \ReflectionException
     */
    public function getMethods(ReflectionClass|string $class): array
    {
        if (is_string($class)) {
            $class = $this->getReflect($class);
        }
        return $this->_classMethod[$class->getName()] ?? [];
    }


    /**
     * @param ReflectionClass $class
     * @return ReflectionProperty[]
     */
    #[Pure] public function getProperty(ReflectionClass $class): array
    {
        return $this->_classProperty[$class->getName()] ?? [];
    }


    /**
     * @param ReflectionClass $class
     * @return array
     */
    #[Pure] public function getPropertyNote(ReflectionClass $class): array
    {
        return $this->_classPropertyNote[$class->getName()] ?? [];
    }


}
