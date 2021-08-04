<?php


namespace Annotation;


use DirectoryIterator;
use Exception;
use ReflectionClass;
use ReflectionException;
use Snowflake\Abstracts\BaseObject;
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
	 * @param $path
	 * @param $namespace
	 * @throws Exception
	 */
	public function loader($path, $namespace)
	{
		$this->_scanDir(new DirectoryIterator($path), $namespace);
	}

	/**
	 * @param string $class
	 * @param string $property
	 * @return mixed
	 * @throws ReflectionException
	 */
	public function getProperty(string $class, string $property = ''): mixed
	{
		return Snowflake::getDi()->getClassReflectionProperty($class, $property);

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
	 * @param object $handler
	 * @return $this
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function injectProperty(string $class, object $handler): static
	{
		$di = Snowflake::getDi();

		$reflect = $di->getReflect($class);

		$di->propertyInject($reflect, $handler);

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
			if (!$replace->getAttributes(Target::class)) {
				return;
			}
			$this->appendFileToDirectory($path->getRealPath(), $replace->getName());

			static::$_classes[] = $replace->getName();
		} catch (Throwable $throwable) {
			write(jTraceEx($throwable), 'throwable');
		}
	}


	/**
	 * @param DirectoryIterator $path
	 * @param string $namespace
	 * @return ReflectionClass|null
	 * @throws ReflectionException
	 */
	private function getReflect(DirectoryIterator $path, string $namespace): ?ReflectionClass
	{
		return Snowflake::getDi()->getReflect($this->explodeFileName($path, $namespace));
	}


	/**
	 * @param string $path
	 * @return array
	 * @throws Exception
	 */
	public function loadByDirectory(string $path): array
	{
		try {
			$path = '/' . trim($path, '/');
			$paths = [];
			foreach (static::$_directory as $key => $_path) {
				$key = '/' . trim($key, '/');
				if (!str_starts_with($key, $path)) {
					continue;
				}
				unset(static::$_directory[$key]);
				foreach ($_path as $item) {
					$paths[] = $item;
				}
			}
			return $paths;
		} catch (Throwable $exception) {
			$this->addError($exception, 'throwable');
			return [];
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


}
