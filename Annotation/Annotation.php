<?php


namespace Annotation;


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
		var_dump(__METHOD__);
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
	 */
	public function instanceDirectoryFiles(string $dir)
	{
		$this->_loader->loadByDirectory($dir);
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
