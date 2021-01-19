<?php


namespace Annotation;


use Annotation\Model\Get;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Snowflake\Abstracts\Component;
use Snowflake\Snowflake;

/**
 * Class Annotation
 * @package Annotation
 */
class Annotation extends Component
{

	private array $_annotations = [];


	private array $_classes = [];


	private array $_targets = [];


	/**
	 * @param string $path
	 * @param string $namespace
	 * @param string $alias
	 * @return $this
	 * @throws ReflectionException
	 */
	public function readControllers(string $path, string $namespace, string $alias = 'root'): static
	{
		$this->scanDir(glob($path . '*'), $namespace, $alias);
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
	 * @param string $namespace
	 * @param string $alias
	 * @return $this
	 * @throws ReflectionException
	 */
	private function scanDir(array $paths, string $namespace, string $alias): static
	{
		if (!isset($this->_annotations[$alias])) {
			$this->_annotations[$alias] = [];
		}
		foreach ($paths as $path) {
			if (!str_contains($path, '.php')) {
				continue;
			}
			$explode = explode('/', $path);

			$explode_pop = array_pop($explode);
			if (is_file($path)) {
				$explode_pop = str_replace('.php', '', $explode_pop);
				$this->getReflect($namespace . '\\' . $explode_pop, $alias);
			} else {
				$this->scanDir(glob($path . '/*'), $namespace . '\\' . $explode_pop, $alias);
			}
		}
		return $this;
	}


	/**
	 * @param string $class
	 * @param string $alias
	 * @return array
	 * @throws ReflectionException
	 */
	private function getReflect(string $class, string $alias): array
	{
		$reflect = $this->reflectClass($class);
		if ($reflect->isInstantiable()) {
			$object = $reflect->newInstance();
			foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				$tmp = $this->resolveAnnotations($method, $alias, $object);

				$this->_classes[$reflect->getName()][$method->getName()] = $tmp;
			}
		}
		return $this->targets($reflect);
	}


	/**
	 * @param string $class
	 * @return ReflectionClass
	 * @throws ReflectionException
	 */
	private function reflectClass(string $class): ReflectionClass
	{
		return Snowflake::getDi()->getReflect($class);
	}


	/**
	 * @param ReflectionMethod $method
	 * @param $alias
	 * @param $object
	 * @return array
	 */
	private function resolveAnnotations(ReflectionMethod $method, $alias, $object): array
	{
		$attributes = $method->getAttributes();
		$this->debug($method->getName());
		if ($method->getName() == 'getGoods_descriptionAttribute') {
			var_dump($method, $attributes);
		}

		if (count($attributes) < 1) {
			return [];
		}

		$names = [];
		foreach ($attributes as $attribute) {
			$names[$attribute->getName()] = $this->instance($attribute);
		}

		$tmp['handler'] = [$object, $method->getName()];
		$tmp['attributes'] = $names;

		$this->_annotations[$alias][] = $tmp;

		return $tmp;
	}


	/**
	 * @param $className
	 * @param string $method
	 * @return array
	 */
	public function getByClass($className, $method = ''): array
	{
		if (!isset($this->_classes[$className])) {
			return [];
		}
		if (empty($method)) {
			return $this->_classes[$className];
		}
		foreach ($this->_classes[$className] as $_method => $class) {
			if ($method == $_method) {
				return [$class, $_method];
			}
		}
		return [];
	}


	/**
	 * @param ReflectionClass $reflect
	 * @return array
	 */
	private function targets(ReflectionClass $reflect): array
	{
		$name = $reflect->getName();
		if (!isset($this->_classes[$name])) {
			$this->_classes[$name] = [];
		}
		$attributes = $reflect->getAttributes();
		if (count($attributes) > 0) {
			if (!isset($this->_targets[$name])) {
				$this->_targets[$name] = [];
			}
			foreach ($attributes as $attribute) {
				$this->_targets[$name][] = $this->instance($attribute);
			}
		}
		return [];
	}


	/**
	 * @param ReflectionAttribute $attribute
	 * @return array|object
	 */
	private function instance(ReflectionAttribute $attribute): array|object
	{
		return $attribute->newInstance();
	}


}
