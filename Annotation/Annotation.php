<?php


namespace Annotation;


use Database\InjectProperty;
use DirectoryIterator;
use Exception;
use Snowflake\Abstracts\Component;
use Snowflake\Exception\ComponentException;

/**
 * Class Annotation
 * @package Annotation
 */
class Annotation extends Component
{


    private Loader $_loader;


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
     * @throws Exception
     */
    public function instanceDirectoryFiles(string $dir, ?string $outPath = null)
    {
        $this->_loader->loadByDirectory($dir, $outPath);
    }


    /**
     * @param string $dir
     * @throws Exception
     */
    public function runtime(string $dir, ?string $outPath = null)
    {
        $this->_loader->directoryRuntime($dir, $outPath);
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
