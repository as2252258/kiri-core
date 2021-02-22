<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:27
 */
declare(strict_types=1);

namespace Snowflake\Di;

use Exception;
use ReflectionClass;
use Snowflake\Abstracts\BaseObject;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Container
 * @package Snowflake\Di
 */
class Container extends BaseObject
{

	/**
	 * @var array
	 *
	 * instance class by className
	 */
	private array $_singletons = [];

	/**
	 * @var array
	 *
	 * class new instance construct parameter
	 */
	private array $_constructs = [];

	/**
	 * @var array
	 *
	 * implements \ReflectClass
	 */
	private array $_reflection = [];

	/**
	 * @var array
	 *
	 * The construct parameter
	 */
	private array $_param = [];


	/**
	 * @var array
	 *
	 * The method attributes
	 */
	private array $_attributes = [];


	/**
	 * @param       $class
	 * @param array $constrict
	 * @param array $config
	 *
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function get($class, $constrict = [], $config = []): mixed
	{
		if (isset($this->_singletons[$class])) {
			return $this->_singletons[$class];
		} else if (!isset($this->_constructs[$class])) {
			return $this->resolve($class, $constrict, $config);
		}
		$definition = $this->_constructs[$class];
		if (is_callable($definition, TRUE)) {
			return call_user_func($definition, $this, $constrict, $config);
		} else if (is_array($definition)) {
			$object = $this->resolveDefinition($definition, $class, $config, $constrict);
		} else if (is_object($definition)) {
			return $this->_singletons[$class] = $definition;
		} else {
			throw new NotFindClassException($class);
		}
		return $this->_singletons[$class] = $object;
	}

	/**
	 * @param $definition
	 * @param $class
	 * @param $config
	 * @param $constrict
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function resolveDefinition($definition, $class, $config, $constrict): mixed
	{
		$config = array_merge($definition, $config);
		$definition = $this->mergeParam($class, $constrict);

		return $this->resolve($class, $definition, $config);
	}

	/**
	 * @param $class
	 * @param $constrict
	 * @param $config
	 *
	 * @return object
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	private function resolve($class, $constrict, $config): object
	{
		[$reflect, $dependencies] = $this->resolveDependencies($class);
		foreach ($constrict as $index => $param) {
			$dependencies[$index] = $param;
		}
		if (!$reflect->isInstantiable()) {
			throw new Exception($reflect->getName() . ' con\'t instantiable');
		}
		var_dump($class, $dependencies);
		if (empty($config)) {
			return $reflect->newInstanceArgs($dependencies ?? []);
		}
		if (!empty($dependencies) && $reflect->implementsInterface('Snowflake\Abstracts\Configure')) {
			$dependencies[count($dependencies) - 1] = $config;
			return $reflect->newInstanceArgs($dependencies);
		}
		$this->_param[$class] = $config;
		$object = $reflect->newInstanceArgs($dependencies ?? []);
		foreach ($config as $key => $val) {
			$object->{$key} = $val;
		}
		if (method_exists($object, 'afterInit')) {
			call_user_func([$object, 'afterInit']);
		}
		return $object;
	}

	/**
	 * @param $class
	 *
	 * @return array
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	private function resolveDependencies($class): array
	{
		$dependencies = [];
		if (!class_exists($class) || !($reflection = $this->resolveClass($class))) {
			return [];
		};
		$constructs = $reflection->getConstructor();
		if (!($constructs instanceof \ReflectionMethod)) {
			return [$reflection, $this->_constructs[$class] = []];
		}
		foreach ($constructs->getParameters() as $key => $param) {
			$dependencies[] = $this->resolveDefaultValue($param);
		}
		$this->_constructs[$class] = $dependencies;
		return [$reflection, $dependencies];
	}


	/**
	 * @param $param
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function resolveDefaultValue(\ReflectionParameter $param): mixed
	{
		if ($param->isOptional()) {
			return $param->getDefaultValue();
		}
		return match ($type = $param->getType()) {
			'mixed' => $param->getDefaultValue(),
			'array' => [],
			'int', 'float' => 0,
			'bool' => false,
			'string', null => '',
			'', 'object' => NULL,
			default => Snowflake::createObject($type)
		};
	}


	/**
	 * @param $class
	 * @return ReflectionClass|null
	 */
	private function resolveClass($class): ?ReflectionClass
	{
		if (!isset($this->_reflection[$class])) {
			$reflection = new ReflectionClass($class);
			if (!$reflection->isInstantiable()) {
				return null;
			}
			$this->_reflection[$class] = $reflection;
		}
		return $this->_reflection[$class];
	}


	/**
	 * @param $class
	 * @return mixed
	 */
	public function getDependencies($class): mixed
	{
		return $this->_constructs[$class] ?? [];
	}


	/**
	 * @param $class
	 * @return ReflectionClass|null
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function getReflect($class): ?ReflectionClass
	{
		if (!isset($this->_reflection[$class])) {
			$this->resolveDependencies($class);
		}
		return $this->_reflection[$class] ?? null;
	}

	/**
	 * @param $class
	 */
	public function unset($class)
	{
		if (is_array($class) && isset($class['class'])) {
			$class = $class['class'];
		} else if (is_object($class)) {
			$class = get_class($class);
		}
		unset(
			$this->_reflection[$class], $this->_singletons[$class],
			$this->_param[$class], $this->_constructs[$class]
		);
	}

	/**
	 * @return $this
	 */
	public function flush(): static
	{
		$this->_reflection = [];
		$this->_singletons = [];
		$this->_param = [];
		$this->_constructs = [];
		return $this;
	}

	/**
	 * @param $class
	 * @param $newParam
	 *
	 * @return mixed
	 */
	private function mergeParam($class, $newParam): array
	{
		if (empty($this->_param[$class])) {
			return $newParam;
		} else if (empty($newParam)) {
			return $this->_param[$class];
		}
		$old = $this->_param[$class];
		foreach ($newParam as $key => $val) {
			$old[$key] = $val;
		}
		return $old;
	}
}
