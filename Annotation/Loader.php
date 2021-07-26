<?php


namespace Annotation;


use Annotation\Model\Get;
use Annotation\Model\Relation;
use Annotation\Model\Set;
use Attribute;
use DirectoryIterator;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Throwable;


/**
 * Class Loader
 * @package Annotation
 */
class Loader extends BaseObject
{


    private static array $_classes = [];


    private static array $_directory = [];


    private static array $_property = [];


    private static array $_methods = [];

    /**
     * @return array
     */
    public function getDirectory(): array
    {
        return static::$_directory;
    }

    /**
     * @param $path
     * @param $namespace
     * @throws Exception
     */
    public function loader($path, $namespace)
    {
        $this->_scanDir(new DirectoryIterator($path), $namespace);
    }


    /**
     * @return array
     */
    public function getClasses(): array
    {
        return static::$_classes;
    }


    /**
     * @param string $class
     * @param string $property
     * @return mixed
     */
    public function getProperty(string $class, string $property = ''): mixed
    {
        if (!isset(static::$_property[$class])) {
            return null;
        }
        if (!empty($property)) {
            return static::$_property[$class][$property] ?? [];
        }
        return static::$_property[$class];
    }


    /**
     * @param string $class
     * @param mixed $handler
     * @return Loader
     */
    public function injectProperty(string $class, object $handler): static
    {
        $properties = $this->getProperty($class);
        if (empty($properties)) {
            return $this;
        }
        foreach ($properties as $property => $attributes) {
            foreach ($attributes as $attribute) {
                $attribute->execute($handler, $property);
            }
        }
        return $this;
    }


    /**
     * @param string $class
     * @param string $method
     * @return mixed
     */
    public function getMethod(string $class, string $method = ''): array
    {
        if (!isset(static::$_methods[$class])) {
            return [];
        }
        $properties = static::$_methods[$class];
        if (!empty($method) && isset($properties[$method])) {
            return $properties[$method];
        }
        return $properties;
    }


    /**
     * @param string $class
     * @return array
     */
    public function getTarget(string $class): array
    {
        return static::$_classes[$class] ?? [];
    }


    /**
     * @param DirectoryIterator $paths
     * @param $namespace
     * @throws Exception
     */
    public function _scanDir(DirectoryIterator $paths, $namespace)
    {
        foreach ($paths as $path) {
            if ($path->isDot() || str_starts_with($path->getFilename(), '.')) {
                continue;
            }
            if ($path->isDir()) {
                $iterator = new DirectoryIterator($path->getRealPath());
                $directory = rtrim($path->getRealPath(), '/');
                if (!isset(static::$_directory[$directory])) {
                    static::$_directory[$directory] = [];
                }
                $this->_scanDir($iterator, $namespace);
            } else {
                $this->readFile($path, $namespace);
            }
        }
    }


    /**
     * @param DirectoryIterator $path
     * @param $namespace
     * @throws Exception
     */
    private function readFile(DirectoryIterator $path, $namespace)
    {
        try {
            if ($path->getExtension() !== 'php') {
                return;
            }
            $replace = $this->getReflect($path, $namespace);
            if (empty($replace) || count($replace->getAttributes(Target::class)) < 1) {
                return;
            }
            $this->appendFileToDirectory($path->getRealPath(), $replace->getName());

            $_array['handler'] = $replace->newInstance();
            $_array['target'] = [];
            $_array['methods'] = [];
            $_array['property'] = [];

            $_array = $this->_targets($replace, $_array);
            $_array = $this->_methods($replace, $_array);
            $_array = $this->_properties($replace, $_array);

            static::$_classes[$replace->getName()] = $_array;
        } catch (Throwable $throwable) {
            $this->addError($throwable, 'throwable');
        }
    }


    /**
     * @param DirectoryIterator $path
     * @param string $namespace
     * @return ReflectionClass|null
     * @throws ReflectionException
     * @throws NotFindClassException
     */
    private function getReflect(DirectoryIterator $path, string $namespace): ?ReflectionClass
    {
        return Snowflake::getDi()->getReflect($this->explodeFileName($path, $namespace));
    }


    /**
     * @param ReflectionClass $replace
     * @param array $_array
     * @return array
     */
    private function _targets(ReflectionClass $replace, array $_array): array
    {
        foreach ($replace->getAttributes() as $attribute) {
            if ($attribute->getName() == Attribute::class) {
                continue;
            }
            if ($attribute->getName() == Target::class) {
                continue;
            }
            $_array['target'][] = $attribute->newInstance();
        }
        return $_array;
    }


    /**
     * @param ReflectionClass $replace
     * @param array $_array
     * @return array
     */
    private function _methods(ReflectionClass $replace, array $_array): array
    {
        $methods = $replace->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $_method = [];
            foreach ($method->getAttributes() as $attribute) {
                if (!class_exists($attribute->getName())) {
                    continue;
                }
                $_method[] = $attribute->newInstance();
            }
            if (!empty($_method)) {
                static::$_methods[$replace->getName()][$method->getName()] = $_method;
            }
        }
        return $_array;
    }


    /**
     * @param ReflectionClass $replace
     * @param array $_array
     * @return array
     */
    private function _properties(ReflectionClass $replace, array $_array): array
    {
        $methods = $replace->getProperties();
        foreach ($methods as $method) {
            $_property = [];
            if ($method->isStatic()) continue;
            foreach ($method->getAttributes() as $attribute) {
                if (!class_exists($attribute->getName())) {
                    continue;
                }
                $_property[] = $attribute->newInstance();
            }
            if (!empty($_property)) {
                static::$_property[$replace->getName()][$method->getName()] = $_property;
            }
        }
        return $_array;
    }


    /**
     * @param string $path
     * @param string|array $outPath
     * @throws Exception
     */
    public function loadByDirectory(string $path, string|array $outPath = '')
    {
        try {
            $path = '/' . trim($path, '/');
            foreach (static::$_directory as $key => $_path) {
                $key = '/' . trim($key, '/');
                if (!str_starts_with($key, $path) || in_array($key, $outPath)) {
                    continue;
                }
                $this->execute($_path);
            }
        } catch (Throwable $exception) {
            $this->addError($exception, 'throwable');
        }
    }


    /**
     * @param DirectoryIterator $path
     * @param string $namespace
     * @return string
     */
    private function explodeFileName(DirectoryIterator $path, string $namespace): string
    {
        $replace = str_replace(APP_PATH . 'app', '', $path->getRealPath());

        $replace = str_replace('.php', '', $replace);
        $replace = str_replace(DIRECTORY_SEPARATOR, '\\', $replace);
        $explode = explode('\\', $replace);
        array_shift($explode);

        return $namespace . '\\' . implode('\\', $explode);
    }


    /**
     * @param string $filePath
     * @param string $className
     */
    public function appendFileToDirectory(string $filePath, string $className)
    {
        $array = explode('/', $filePath);
        unset($array[count($array) - 1]);

        $array = '/' . trim(implode('/', $array), '/');

        static::$_directory[$array][] = $className;
    }


    /**
     * @param array $classes
     * @throws Exception
     */
    private function execute(array $classes)
    {
        if (empty($classes)) {
            return;
        }
        foreach ($classes as $className) {
            if (!isset(static::$_methods[$className])) {
                continue;
            }
            foreach (static::$_methods[$className] as $name => $attribute) {
                $this->methods($attribute, $className, $name);
            }
        }
    }


    /**
     * @param $attribute
     * @param $annotation
     * @param $className
     * @param $name
     */
    private function methods($attribute, $className, $name)
    {
        $handler = static::$_classes[$className]['handler'];
        foreach ($attribute as $value) {
            $value->execute($handler, $name);
        }
    }

}
