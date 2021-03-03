<?php


namespace Annotation;


use DirectoryIterator;
use Snowflake\Abstracts\Component;

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
	 * @return array 根据类名获取注解
	 * 根据类名获取注解
	 */
	public function getMethods(string $className): array
	{
		return $this->_loader->getMethod($className);
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
	 */
	public function read(string $path, string $namespace, string $alias = 'root'): void
	{

		$this->_loader->_scanDir(new DirectoryIterator($path), $namespace);
	}

}
