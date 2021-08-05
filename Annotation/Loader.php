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


	private array $_classes = [];


	private array $_directory = [];


	private array $_property = [];


	private array $_methods = [];


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
	 * @return \ReflectionProperty|array|null
	 * @throws ReflectionException
	 */
	public function getProperty(string $class, string $property = ''): \ReflectionProperty|array|null
	{
		return Snowflake::getDi()->getClassReflectionProperty($class, $property);
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
		if (!isset($this->_methods[$class])) {
			return [];
		}
		$properties = $this->_methods[$class];
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
			$replace = $this->getReflect($path, $namespace);
			if (!$replace->getAttributes(Target::class)) {
				return;
			}
			$this->appendFileToDirectory($path->getRealPath(), $replace->getName());
		} catch (Throwable $throwable) {
			$this->error(jTraceEx($throwable), 'throwable');
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
	 * @param array $exclude
	 * @return array
	 * @throws Exception
	 */
	public function loadByDirectory(string $path, array $exclude = []): array
	{
		try {
			$path = '/' . trim($path, '/');
			$paths = [];
			foreach ($this->_directory as $key => $_path) {
				$key = '/' . trim($key, '/');
				if (!str_starts_with($key, $path) || $this->inExclude($exclude, $path)) {
					continue;
				}
				unset($this->_directory[$key]);
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
	 * @param array $exclude
	 * @param $path
	 * @return bool
	 */
	private function inExclude(array $exclude, $path): bool
	{
		if (empty($exclude)) {
			return false;
		}
		foreach ($exclude as $value) {
			if (str_starts_with($value, $path)) {
				return true;
			}
		}
		return false;
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
	}


}
