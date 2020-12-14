<?php


namespace Annotation;


use ReflectionAttribute;
use ReflectionException;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;

/**
 * Class Annotation
 * @package Annotation
 */
class Annotation extends Component
{

	private array $_annotations = [];


	private array $_targets = [];


	/**
	 * @param string $path
	 * @param string $alias
	 * @return $this
	 * @throws ReflectionException
	 */
	public function readControllers(string $path, string $alias = 'root'): static
	{
		foreach (glob($path . '/*') as $dir) {
			$this->scanDir($dir, $alias);
		}
		return $this;
	}


	/**
	 * @param string $alias
	 * @return array
	 */
	public function getAlias(string $alias = 'root'): array
	{
		if (!isset($this->_annotations[$alias])) {
			return [];
		}
		return $this->_annotations[$alias];
	}


	/**
	 * @param string $target
	 * @return mixed
	 */
	public function getTarget(string $target): mixed
	{
		if (!isset($this->_targets[$target])) {
			return [];
		}
		return $this->_targets[$target];
	}


	/**
	 * @param array $paths
	 * @param string $alias
	 * @return $this
	 * @throws ReflectionException
	 */
	private function scanDir(array $paths, string $alias): static
	{
		if (!isset($this->_annotations[$alias])) {
			$this->_annotations[$alias] = [];
		}
		foreach ($paths as $path) {
			if (is_file($path)) {
				$this->_annotations[$alias][] = $this->getReflect($path);
			} else {
				$this->scanDir($path, $alias);
			}
		}
		return $this;
	}


	/**
	 * @param $class
	 * @return array
	 * @throws ReflectionException
	 */
	private function getReflect($class): array
	{
		$reflect = Snowflake::getDi()->getReflect($class);

		$this->targets($reflect);

		$annotations = [];
		$object = $reflect->newInstance();
		foreach ($reflect->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			$names = [];
			$attributes = $method->getAttributes();
			if (count($attributes) < 1) {
				continue;
			}
			foreach ($attributes as $attribute) {
				$names[$attribute->getName()] = $this->instance($attribute);
			}
			$tmp['handler'] = [$object, $method->getName()];
			$tmp['attributes'] = $names;

			$annotations[] = $tmp;
		}
		return $annotations;
	}

	/**
	 * @param \ReflectionClass $reflect
	 */
	private function targets(\ReflectionClass $reflect)
	{
		$attributes = $reflect->getAttributes();
		if (count($attributes) < 1) {
			return;
		}

		if (!isset($this->_targets[$reflect->getName()])) {
			$this->_targets[$reflect->getName()] = [];
		}

		foreach ($attributes as $attribute) {
			$this->_targets[$reflect->getName()][] = $this->instance($attribute);
		}
	}


	/**
	 * @param ReflectionAttribute $attribute
	 * @return array|object
	 */
	private function instance(ReflectionAttribute $attribute): array|object
	{
		$instance = $attribute->newInstance();
		if ($instance instanceof Middleware) {
			$instance = [$instance, 'onHandler'];
		}
		if ($instance instanceof Interceptor) {
			$instance = [$instance, 'onHandler'];
		}
		if ($instance instanceof Limits) {
			$instance = [$instance, 'onHandler'];
		}
		if ($instance instanceof After) {
			$instance = [$instance, 'onHandler'];
		}
		return $instance;
	}


}
