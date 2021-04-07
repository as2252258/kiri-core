<?php


namespace Annotation;


use Attribute;
use DirectoryIterator;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Throwable;


/**
 * Class Loader
 * @package Annotation
 */
class Loader extends BaseObject
{


    private array $_classes = [];


    private array $_fileMap = [];


    private array $_directoryMap = [];


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
        return $this->_classes;
    }


    /**
     * @param string $class
     * @param string $property
     * @return mixed
     */
    public function getProperty(string $class, string $property = ''): mixed
    {
        if (!isset($this->_classes[$class])) {
            return null;
        }
        $properties = $this->_classes[$class]['property'];
        if (!empty($property) && isset($properties[$property])) {
            return $properties[$property];
        }
        return $properties;
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
                $attribute->execute([$handler, $property]);
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
        if (!isset($this->_classes[$class])) {
            return [];
        }
        $properties = $this->_classes[$class]['methods'];
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
        return $this->_classes[$class] ?? [];
    }


    /**
     * @param DirectoryIterator $paths
     * @param $namespace
     * @throws Exception
     */
    public function _scanDir(DirectoryIterator $paths, $namespace)
    {
        /** @var DirectoryIterator $path */
        $DIRECTORY = $this->createDirectoryMap($paths);
        foreach ($paths as $path) {
            if ($path->isDot()) continue;

            if (str_starts_with($path->getFilename(), '.')) {
                continue;
            }
            if ($path->isDir()) {
                $this->_scanDir(new DirectoryIterator($path->getRealPath()), $namespace);
                continue;
            }

            if ($path->getExtension() !== 'php') {
                continue;
            }
            try {

                $replace = Snowflake::getDi()->getReflect($this->explodeFileName($path, $namespace));
                if (empty($replace) || !$replace->isInstantiable()) {
                    continue;
                }

                if (!$replace->getAttributes(Target::class)) {
                    continue;
                }
                $this->appendFileToDirectory($path->getRealPath(), $replace->getName());

                $_array = ['handler' => $replace->newInstanceWithoutConstructor(), 'target' => [], 'methods' => [], 'property' => []];
                foreach ($replace->getAttributes() as $attribute) {
                    if ($attribute->getName() == Attribute::class) {
                        continue;
                    }
                    $_array['target'][] = $attribute->newInstance();
                }

                $methods = $replace->getMethods(ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    $_method = [];
                    foreach ($method->getAttributes() as $attribute) {
                        if (!class_exists($attribute->getName())) {
                            continue;
                        }
                        $_method[] = $attribute->newInstance();
                    }
                    $_array['methods'][$method->getName()] = $_method;
                }

                $methods = $replace->getProperties();
                foreach ($methods as $method) {
                    $_property = [];
                    if ($method->isStatic()) continue;
                    foreach ($method->getAttributes() as $attribute) {
                        if (!class_exists($attribute->getName())) {
                            continue;
                        }
//						$property = $attribute->newInstance();
//						if ($property instanceof Inject) {
//							$property->execute([$_array['handler'], $method]);
//						}
                        $_property[] = $attribute->newInstance();
                    }
                    $_array['property'][$method->getName()] = $_property;
                }

                $this->_fileMap[$replace->getFileName()] = $replace->getName();

                $this->_classes[$replace->getName()] = $_array;
            } catch (Throwable $throwable) {
                $this->addError($throwable, 'throwable');
            }
        }
    }


    /**
     * @param string $path
     * @throws Exception
     */
    public function loadByDirectory(string $path, ?string $outPath = null)
    {
        try {
            foreach ($this->_fileMap as $fileName => $className) {
                if (str_starts_with($fileName, $outPath)) {
                    continue;
                }
                if (!str_starts_with($fileName, $path)) {
                    continue;
                }
                if (!isset($this->_classes[$className])) {
                    continue;
                }

                $annotations = $this->_classes[$className];
                if (isset($annotations['target']) && !empty($annotations['target'])) {
                    foreach ($annotations['target'] as $value) {
                        $value->execute([$annotations['handler']]);
                    }
                }

                foreach ($annotations['methods'] as $name => $attribute) {
                    foreach ($attribute as $value) {
                        if (!($value instanceof \Annotation\Attribute)) {
                            continue;
                        }
                        $value->execute([$annotations['handler'], $name]);
                    }
                }
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
     * @param DirectoryIterator $directoryIterator
     * @return string
     */
    public function createDirectoryMap(DirectoryIterator $directoryIterator): string
    {
        $DIRECTORY = explode(DIRECTORY_SEPARATOR, $directoryIterator->getRealPath());
        array_pop($DIRECTORY);

        $path = DIRECTORY_SEPARATOR;
        foreach ($DIRECTORY as $value) {
            $path = $this->makeMoneyDirectoryArray($path, $value);
        }
        return $DIRECTORY;
    }


    /**
     * @param $path
     * @param $value
     * @return string
     */
    private function makeMoneyDirectoryArray($path, $value)
    {
        $path .= $value . DIRECTORY_SEPARATOR;
        if (!isset($this->_directoryMap[$path])) {
            $this->_directoryMap[$path] = [];
        }
        return $path;
    }


    /**
     * @param string $filePath
     * @param string $className
     */
    public function appendFileToDirectory(string $filePath, string $className)
    {
        $DIRECTORY = explode(DIRECTORY_SEPARATOR, $filePath);
        array_pop($DIRECTORY);

        $path = DIRECTORY_SEPARATOR;
        foreach ($DIRECTORY as $value) {
            $path = $this->makeMoneyDirectoryArray($path, $value);

            $this->_directoryMap[$path][] = $className;
        }
    }


    /**
     * @param string $Directory
     * @return array
     */
    public function getDirectoryFiles(string $Directory): array
    {
        if (!isset($this->_directoryMap[$Directory])) {
            return [];
        }
        return $this->_directoryMap[$Directory];
    }


    /**
     * @param string $Directory
     * @param string|null $output
     */
    public function directoryRuntime(string $Directory, ?string $output)
    {
        if (!empty($output) && isset($this->_directoryMap[$output])) {
            unset($this->_directoryMap[$output]);
        }
        $this->execute($Directory);
    }


    /**
     * @param string $filename
     * @return mixed
     */
    public function getClassByFilepath(string $filename): mixed
    {
        if (!isset($this->_fileMap[$filename])) {
            return null;
        }
        return $this->_classes[$this->_fileMap[$filename]];
    }


    /**
     * @param $directory
     */
    private function execute(string $directory)
    {
        if (!isset($this->_directoryMap[$directory])) {
            return;
        }

        $directories = $this->_directoryMap[$directory];
        foreach ($directories as $className) {
            if (!isset($this->_classes[$className])) {
                continue;
            }

            $annotations = $this->_classes[$className];
            if (isset($annotations['target']) && !empty($annotations['target'])) {
                foreach ($annotations['target'] as $value) {
                    $value->execute([$annotations['handler']]);
                }
            }

            foreach ($annotations['methods'] as $name => $attribute) {
                foreach ($attribute as $value) {
                    if (!($value instanceof \Annotation\Attribute)) {
                        continue;
                    }
                    $value->execute([$annotations['handler'], $name]);
                }
            }
        }
    }

}
