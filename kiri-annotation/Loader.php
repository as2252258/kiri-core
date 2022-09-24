<?php


namespace Kiri\Annotation;


use DirectoryIterator;
use Exception;
use Kiri;
use Kiri\Abstracts\Component;
use ReflectionClass;
use ReflectionException;
use Throwable;


/**
 * Class Loader
 * @package Annotation
 */
class Loader extends Component
{


	private array $_directory = [];


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
	 * @param object $handler
	 * @return $this
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function injectProperty(string $class, object $handler): static
	{
		$di = Kiri::getDi();

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
	 * @param array $exclude
	 * @throws Exception
	 */
	public function _scanDir(DirectoryIterator $paths, $namespace, array $exclude = [])
	{
		foreach ($paths as $path) {
			if (function_exists('opcache_invalidate')) {
				opcache_invalidate($path->getRealPath(), true);
			}
			if ($path->isDot() || str_starts_with($path->getFilename(), '.')) {
				continue;
			}
			if ($this->inExclude($exclude, $path->getRealPath())) {
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
			if (!$replace || !$replace->getAttributes(Target::class)) {
				return;
			}
			$this->appendFileToDirectory($path->getRealPath(), $replace->getName());
		} catch (Throwable $throwable) {
			$this->logger->error(jTraceEx($throwable));
		}
	}


	/**
	 * @param DirectoryIterator $path
	 * @param string $namespace
	 * @return ReflectionClass|null
	 */
	private function getReflect(DirectoryIterator $path, string $namespace): ?ReflectionClass
	{
		$class = $this->explodeFileName($path, $namespace);
		if (!class_exists($class)) {
			return null;
		}
		return Kiri::getDi()->getReflect($class);
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
			$this->logger->addError($exception, 'throwable');
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
			if (str_starts_with($path, $value)) {
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
		$replace = str_replace(APP_PATH, '', $path->getRealPath());

		$replace = str_replace('.php', '', $replace);
		$replace = str_replace(DIRECTORY_SEPARATOR, '\\', $replace);
		$explode = explode('\\', $replace);
		array_shift($explode);
		
		var_dump($namespace . '\\' . implode('\\', $explode));

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
