<?php


namespace Annotation;


use Annotation\Model\Get;
use Annotation\Model\Relation;
use Annotation\Model\Set;
use Attribute;
use DirectoryIterator;
use Exception;
use ReflectionMethod;
use Snowflake\Abstracts\BaseObject;
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


    private array $_directory = [];


    private FileTree $files;


    /**
     * @return array
     */
    public function getDirectory(): array
    {
        return $this->_directory;
    }


    public function init()
    {
        $this->files = new FileTree();
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
        foreach ($paths as $path) {
            if ($path->isDot() || str_starts_with($path->getFilename(), '.')) {
                continue;
            }
            if ($path->isDir()) {
                $iterator = new DirectoryIterator($path->getRealPath());
                $directory = rtrim($path->getRealPath(), '/');
                if (!isset($this->_directory[$directory])) {
                    $this->_directory[$directory] = [];
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
            $replace = Snowflake::getDi()->getReflect($this->explodeFileName($path, $namespace));
            if (empty($replace) || count($replace->getAttributes(Target::class)) < 1) {
                return;
            }
            $this->appendFileToDirectory($path->getRealPath(), $replace->getName());

            $_array = ['handler' => $replace->newInstance(), 'target' => [], 'methods' => [], 'property' => []];
            foreach ($replace->getAttributes() as $attribute) {
                if ($attribute->getName() == Attribute::class) {
                    continue;
                }
                if ($attribute->getName() == Target::class) {
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


    /**
     * @param string $path
     * @param string|array $outPath
     * @throws Exception
     */
    public function loadByDirectory(string $path, string|array $outPath = '')
    {
        try {
            $path = '/' . trim($path, '/');
            foreach ($this->_directory as $key => $_path) {
                $key = '/' . trim($key, '/');
                if (!str_starts_with($key, $path)) {
                    continue;
                }
                if (in_array($key, $outPath)) {
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

        $this->_directory[$array][] = $className;

//		$directory = $this->splitDirectory($filePath);
//		array_pop($directory);
//
//		$tree = null;
//		foreach ($directory as $value) {
//			$tree = $this->getTree($tree, $value);
//		}
//
//		if ($tree instanceof FileTree) {
//			$tree->addFile($className, $filePath);
//		}
    }


    /**
     * @param string $filePath
     * @param string|null $outPath
     * @return $this
     * @throws Exception
     */
    private function each(string $filePath, ?string $outPath): static
    {
        $tree = null;
        $directory = $this->splitDirectory($filePath);

        $_tmp = '';
        if (!empty($outPath)) {
            $outPath = rtrim($outPath, '/');
        }

        foreach ($directory as $key => $value) {
            $_tmp .= DIRECTORY_SEPARATOR . $value;
            if (!empty($outPath) && str_contains($_tmp, $outPath)) {
                break;
            }
            $tree = $this->getTree($tree, $value);
        }
        if ($tree instanceof FileTree) {
            $this->eachNode($tree->getChildes(), $outPath);
            $this->execute($tree->getFiles());
        }
        return $this;
    }


    /**
     * @param string $filePath
     * @return false|string[]
     */
    private function splitDirectory(string $filePath): array|bool
    {
        $DIRECTORY = explode(DIRECTORY_SEPARATOR, $filePath);
        return array_filter($DIRECTORY, function ($value) {
            return !empty($value);
        });
    }


    /**
     * @param $tree
     * @param $value
     * @return FileTree
     */
    private function getTree($tree, $value): FileTree
    {
        if ($tree === null) {
            $tree = $this->files->getChild($value);
        } else {
            $tree = $tree->getChild($value);
        }
        return $tree;
    }


    /**
     * @param FileTree[] $nodes
     * @param string|null $outPath
     * @throws Exception
     */
    private function eachNode(array $nodes, ?string $outPath = '')
    {
        foreach ($nodes as $node) {
            $this->execute($node->getFiles());
            if (!empty($outPath) && str_contains($node->getDirPath(), $outPath)) {
                continue;
            }
            $childes = $node->getChildes();
            if (!empty($childes)) {
                $this->eachNode($childes, $outPath);
            }
        }
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
     * @param array $classes
     */
    private function execute(array $classes)
    {
        if (empty($classes)) {
            return;
        }
        $annotation = Snowflake::app()->getAnnotation();
        foreach ($classes as $className) {
            $annotations = $this->_classes[$className] ?? null;
            if ($annotations === null) {
                continue;
            }
            foreach ($annotations['target'] ?? [] as $value) {
                $value->execute([$annotations['handler']]);
            }

            $_className = $annotations['handler']::class;
            foreach ($annotations['methods'] as $name => $attribute) {
                foreach ($attribute as $value) {
                    if ($value instanceof Relation) {
                        $annotation->addRelate($_className, $value->name, $name);
                    } else if ($value instanceof Get) {
                        $annotation->addGets($_className, $value->name, $name);
                    } else if ($value instanceof Set) {
                        $annotation->addSets($_className, $value->name, $name);
                    } else {
                        $value->execute([$annotations['handler'], $name]);
                    }
                }
            }
            unset($this->_classes[$className]);
        }
    }

}
