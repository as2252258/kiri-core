<?php


namespace Kiri\Annotation;


use DirectoryIterator;
use Exception;
use ReflectionException;
use Kiri\Abstracts\Component;

/**
 * Class Annotation
 * @package Annotation
 */
class Annotation extends Component
{


	private Loader $_loader;

    /**
     *
     */
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
	 * @param object $class
	 * @throws ReflectionException
	 */
	public function injectProperty(object $class)
	{
		$this->_loader->injectProperty($class::class, $class);
	}


	/**
	 * @param string $path
	 * @param string $namespace
	 * @param array $exclude
	 * @return static
	 * @throws Exception
	 */
	public function read(string $path, string $namespace = 'App', array $exclude = []): static
	{
		$this->_loader->_scanDir(new DirectoryIterator($path), $namespace, $exclude);
		return $this;
	}


	/**
	 * @param string $dir
	 * @param array $exclude
	 * @return array
	 * @throws Exception
	 */
	public function runtime(string $dir, array $exclude = []): array
	{
		return $this->_loader->loadByDirectory($dir, $exclude);
	}


}
