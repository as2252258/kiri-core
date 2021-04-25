<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:27
 */
declare(strict_types=1);

namespace Snowflake\Di;

use Annotation\Inject;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Snowflake\Abstracts\BaseObject;
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


	private array $_property = [];


	/**
	 * @var array
	 *
	 * The construct parameter
	 */
	private array $_param = [];

	/**
	 * @param       $class
	 * @param array $constrict
	 * @param array $config
	 *
	 * @return mixed
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
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
	 * @throws Exception
	 */
	private function resolveDefinition($definition, $class, $config, $constrict): mixed
	{
		if (!isset($definition['class'])) {
			throw new NotFindClassException($class);
		}
		$_className = $definition['class'];
		unset($definition['class']);

		$config = array_merge($definition, $config);
		$definition = $this->mergeParam($class, $constrict);

		if ($_className === $class) {
			$object = $this->resolve($class, $definition, $config);
		} else {
			$object = $this->get($class, $definition, $config);
		}
		return $object;
	}


	/**
	 * @param $class
	 * @param $constrict
	 * @param $config
	 *
	 * @return object
	 * @throws Exception
	 */
	private function resolve($class, $constrict, $config): object
	{
		/** @var ReflectionClass $reflect */
		[$reflect, $dependencies] = $this->resolveDependencies($class);
		if (empty($reflect)) {
			throw new NotFindClassException($class);
		}
		foreach ($constrict as $index => $param) {
			$dependencies[$index] = $param;
		}

		if (!$reflect->isInstantiable()) {
			throw new NotFindClassException($reflect->getName());
		}

		if (empty($config) || !is_array($config)) {
			$object = $reflect->newInstanceArgs($dependencies);
		} else if (!empty($dependencies) && $reflect->implementsInterface('Snowflake\Abstracts\Configure')) {
			$dependencies[count($dependencies) - 1] = $config;
			$object = $reflect->newInstanceArgs($dependencies);
		} else {
			if (!empty($config)) $this->_param[$class] = $config;

			$object = $this->onAfterInit($reflect->newInstanceArgs($dependencies), $config);
		}
		return $this->propertyInject($reflect, $object);
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param $object
	 * @return mixed
	 * @throws Exception
	 */
	private function propertyInject(ReflectionClass $reflect, $object): mixed
	{
		if (!isset($this->_property[$reflect->getName()])) {
			return $object;
		}
		foreach ($this->_property[$reflect->getName()] as $property => $inject) {
			/** @var Inject $inject */
			$inject->execute([$object, $property]);
		}
		return $object;
	}


	/**
	 * @param string $class
	 * @param string|null $property
	 * @return ReflectionProperty|array|null
	 */
	public function getClassProperty(string $class, string $property = null): ReflectionProperty|null|array
	{
		if (!isset($this->_property[$class])) {
			return null;
		}
		$properties = $this->_property[$class];
		if (!empty($property)) {
			return $properties[$property] ?? null;
		}
		return $properties;
	}


	/**
	 * @param $object
	 * @param $config
	 * @return mixed
	 */
	private function onAfterInit($object, $config): mixed
	{
		Snowflake::configure($object, $config);
		if (method_exists($object, 'afterInit')) {
			call_user_func([$object, 'afterInit']);
		}
		return $object;
	}

	/**
	 * @param $class
	 * @return array|null
	 * @throws ReflectionException|NotFindClassException
	 */
	private function resolveDependencies($class): ?array
	{
		if (isset($this->_reflection[$class])) {
			return [$this->_reflection[$class], $this->_constructs[$class] ?? []];
		}

		$reflection = new ReflectionClass($class);
		if (!$reflection->isInstantiable()) {
			return null;
		}

		$this->scanProperty($reflection);

		$this->_reflection[$class] = $reflection;

		if (!is_null($construct = $reflection->getConstructor())) {
			$this->_constructs[$class] = $this->resolveMethodParam($construct);
		}

		return [$reflection, $this->_constructs[$class] ?? []];
	}


	/**
	 * @param ReflectionClass $reflectionClass
	 * @return $this
	 */
	private function scanProperty(ReflectionClass $reflectionClass): static
	{
		$lists = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC |
			ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED
		);

		$className = $reflectionClass->getName();
		foreach ($lists as $list) {
			$targets = $list->getAttributes(Inject::class);
			if (count($targets) < 1) {
				continue;
			}

			$this->_property[$className][$list->getName()] = $targets[0]->newInstance();
		}
		return $this;
	}


	/**
	 * @param ReflectionMethod|null $method
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function resolveMethodParam(?ReflectionMethod $method): array
	{
		$array = [];
		foreach ($method->getParameters() as $key => $parameter) {
			if ($parameter->isDefaultValueAvailable()) {
				$array[] = $parameter->getDefaultValue();
			} else {
				$type = $parameter->getType();
				if (is_string($type) && class_exists($type)) {
					$type = Snowflake::createObject($type);
				}
				$array[] = match ($parameter->getType()) {
					'string' => '',
					'int', 'float' => 0,
					'', null, 'object', 'mixed' => NULL,
					'bool' => false,
					default => $type
				};
			}
		}
		return $array;
	}


	/**
	 * @param $class
	 * @return ReflectionClass|null
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function getReflect($class): ?ReflectionClass
	{
		$reflect = $this->_reflection[$class] ?? null;
		if (!is_null($reflect)) {
			return $reflect;
		}
		$reflect = $this->resolveDependencies($class);
		if (is_array($reflect)) {
			return $reflect[0];
		}
		return null;
	}

	/**
	 * @param $class
	 */
	public function unset($class)
	{
		if (is_array($class) && isset($class['class'])) {
			$class = $class['class'];
		} else if (is_object($class)) {
			$class = $class::class;
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
