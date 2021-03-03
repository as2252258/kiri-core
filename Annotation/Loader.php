<?php


namespace Annotation;


use Attribute;
use DirectoryIterator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
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


	private array $_directoryMap = [];


	/**
	 * @param $path
	 * @param $namespace
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
	public function getMethod(string $class, string $method = ''): mixed
	{
		if (!isset($this->_classes[$class])) {
			return null;
		}
		$properties = $this->_classes[$class]['methods'];
		if (!empty($property) && isset($properties[$method])) {
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
	 */
	public function _scanDir(DirectoryIterator $paths, $namespace)
	{
		$DIRECTORY = $this->createDirectoryMap($paths);
		foreach ($paths as $path) {
			if ($path->getFilename() === '.' || $path->getFilename() === '..') {
				continue;
			}
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

			if (!in_array($path->getRealPath(), $this->_directoryMap[$DIRECTORY])) {
				$this->_directoryMap[$DIRECTORY][] = $path->getRealPath();
			}

			try {
				$replace = Snowflake::getDi()->getReflect($this->explodeFileName($path, $namespace));
				if (empty($replace) || !$replace->isInstantiable()) {
					continue;
				}

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

				$methods = $replace->getProperties(ReflectionMethod::IS_PUBLIC ^ ReflectionProperty::IS_STATIC);
				foreach ($methods as $method) {
					$_property = [];
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
				$this->error($throwable->getMessage());
				$this->error($throwable->getFile());
				$this->error($throwable->getLine());
			}
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

		echo $namespace . '\\' . implode('\\', $explode) . PHP_EOL;

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

		$DIRECTORY = implode(DIRECTORY_SEPARATOR, $DIRECTORY);

		if (!isset($this->_directoryMap[$DIRECTORY])) {
			$this->_directoryMap[$DIRECTORY] = [];
		}
		return $DIRECTORY;
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
}
