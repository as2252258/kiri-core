<?php


namespace Annotation;


use DirectoryIterator;
use Exception;
use Snowflake\Abstracts\Component;

/**
 * Class Annotation
 * @package Annotation
 */
class Annotation extends Component
{


    private Loader $_loader;


    private array $_model_sets = [];
    private array $_model_gets = [];
    private array $_model_relate = [];


    /**
     * @param string $class
     * @param string $setName
     * @param string $method
     */
    public function addSets(string $class, string $setName, string $method)
    {
        $this->_model_sets[$class][$setName] = $method;
    }

    /**
     * @param string $class
     * @param string $setName
     * @param string $method
     */
    public function addGets(string $class, string $setName, string $method)
    {
        $this->_model_gets[$class][$setName] = $method;
    }


    /**
     * @param string $class
     * @param string $setName
     * @param string $method
     */
    public function addRelate(string $class, string $setName, string $method)
    {
        $this->_model_relate[$class][$setName] = $method;
    }


    /**
     * @param string $class
     * @param string $setName
     * @return mixed|null
     */
    public function getGetMethodName(string $class, string $setName): ?string
    {
        $gets = $this->_model_gets[$class] ?? $this->_model_relate[$class] ?? null;
        if ($gets == null) {
            return null;
        }
        return $gets[$setName] ?? null;
    }


    /**
     * @param string $class
     * @return array
     */
    public function getModelMethods(string $class): array
    {
        return $this->_model_gets[$class] ?? [];
    }



    /**
     * @param string $class
     * @param string $setName
     * @return mixed|null
     */
    public function getSetMethodName(string $class, string $setName): ?string
    {
        if (!isset($this->_model_sets[$class])) {
            return null;
        }

        $lists = $this->_model_sets[$class];

        if (isset($lists[$setName])) {
            return $lists[$setName];
        }
        return null;
    }


    public function init(): void
    {
        $this->_loader = new Loader();
    }


    /**
     * @return Loader
     */
    public function getLoader(): Loader
    {
        return $this->_loader;
    }


    /**
     * @param Loader $loader
     * @return Loader
     */
    public function setLoader(Loader $loader): Loader
    {
        return $this->_loader = $loader;
    }


    /**
     * @param string $className
     * @param string $method
     * @return array 根据类名获取注解
     * 根据类名获取注解
     */
    public function getMethods(string $className, string $method = ''): mixed
    {
        return $this->_loader->getMethod($className, $method);
    }


    /**
     * @param object $class
     */
    public function injectProperty(object $class)
    {
        $this->_loader->injectProperty(get_class($class), $class);
    }


    /**
     * @param string $path
     * @param string $namespace
     * @param string $alias
     * @return void
     * @throws Exception
     */
    public function read(string $path, string $namespace, string $alias = 'root'): void
    {

        $this->_loader->_scanDir(new DirectoryIterator($path), $namespace);
    }


    /**
     * @param string $dir
     * @param string|array $outPath
     * @throws Exception
     */
    public function runtime(string $dir, string|array $outPath = '')
    {
        if (empty($outPath)) {
            $outPath = [];
        } else if (is_string($outPath)) {
            $outPath = [$outPath];
        }
        $this->_loader->loadByDirectory($dir, $outPath);
    }


    /**
     * @param string $filename
     * @return mixed
     */
    public function getFilename(string $filename): mixed
    {
        return $this->_loader->getClassByFilepath($filename);
    }

}
