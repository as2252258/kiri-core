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
use ReflectionFunction;
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

	use Attributes;

	/**
	 * @var array
	 *
	 * instance class by className
	 */
	private array $_singletons = [];

	/**
	 * @var ReflectionMethod[]
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


	/** @var array */
	private array $_parameters = [];


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
	public function get($class, array $constrict = [], array $config = []): mixed
	{
		if (!isset($this->_singletons[$class])) {
			$this->_singletons[$class] = $this->resolve($class, $constrict, $config);
		}
		return $this->_singletons[$class];
	}


	/**
	 * @param $class
	 * @param array $constrict
	 * @param array $config
	 * @return object
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function newObject($class, array $constrict = [], array $config = []): object
	{
		return $this->resolve($class, $constrict, $config);
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
		$reflect = $this->resolveDependencies($class);
		if (!$reflect->isInstantiable()) {
			throw new ReflectionException('Class ' . $class . ' cannot be instantiated');
		}

		$object = $this->newInstance($reflect, $constrict);

		$this->propertyInject($reflect, $object);

		return $this->onAfterInit($object, $config);
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param $dependencies
	 * @return object
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	private function newInstance(ReflectionClass $reflect, $dependencies): object
	{
		if (!isset($this->_constructs[$reflect->getName()])) {
			return $reflect->newInstance();
		}
		$construct = $this->_constructs[$reflect->getName()];
		if ($construct->getNumberOfParameters() < 1) {
			return $reflect->newInstance();
		}
		$parameters = $this->mergeParam($this->resolveMethodParameters($construct), $dependencies);
		return $reflect->newInstanceArgs($parameters);
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param $object
	 * @return mixed
	 * @throws Exception
	 */
	public function propertyInject(ReflectionClass $reflect, $object): mixed
	{
		foreach ($this->getPropertyNote($reflect) as $property => $inject) {
			/** @var Inject $inject */
			$inject->execute($object, $property);
		}
		return $object;
	}


	/**
	 * @param $className
	 * @param $method
	 * @return array
	 * @throws ReflectionException
	 */
	public function getMethodAttribute($className, $method = null): array
	{
		$methods = $this->getMethodNote($this->getReflect($className));
		if (!empty($method)) {
			return $methods[$method] ?? [];
		}
		return $methods;
	}


	/**
	 * @param string $class
	 * @param string|null $property
	 * @return ReflectionProperty|ReflectionProperty[]|null
	 * @throws ReflectionException
	 */
	public function getClassReflectionProperty(string $class, string $property = null): ReflectionProperty|null|array
	{
		$lists = $this->getProperty($this->getReflect($class));
		if (empty($lists)) {
			return null;
		}
		if (!empty($property)) {
			return $lists[$property] ?? null;
		}
		return $lists;
	}


	/**
	 * @param $object
	 * @param $config
	 * @return mixed
	 */
	private function onAfterInit($object, $config): mixed
	{
		Snowflake::configure($object, $config);
		if (method_exists($object, 'init')) {
			call_user_func([$object, 'init']);
		}
		return $object;
	}


	/**
	 * @param $class
	 * @return ReflectionClass
	 */
	private function resolveDependencies($class): ReflectionClass
	{
		$reflect = new ReflectionClass($class);
		if ($reflect->isAbstract() || $reflect->isTrait() || $reflect->isInterface()) {
			return $this->_reflection[$class] = $reflect;
		}
		$this->setPropertyNote($reflect);
		$this->setTargetNote($reflect);
		$this->setMethodNote($reflect);
		$construct = $reflect->getConstructor();
		if (!empty($construct) && $construct->getNumberOfParameters() > 0) {
			$this->_constructs[$class] = $construct;
		}
		return $this->_reflection[$class] = $reflect;
	}


	/**
	 * @param ReflectionClass|string $class
	 * @return ReflectionMethod[]
	 * @throws ReflectionException
	 */
	public function getReflectMethods(ReflectionClass|string $class): array
	{
		if (is_string($class)) {
			$class = $this->getReflect($class);
		}
		return $this->getMethods($class);
	}


	/**
	 * @param ReflectionClass|string $class
	 * @param string $method
	 * @return ReflectionMethod|null
	 * @throws ReflectionException
	 */
	public function getReflectMethod(ReflectionClass|string $class, string $method): ?ReflectionMethod
	{
		return $this->getReflectMethods($class)[$method] ?? null;
	}


	/**
	 * @param ReflectionClass|string $class
	 * @param string $method
	 * @return array|null
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function getMethodParameters(ReflectionClass|string $class, string $method): ?array
	{
		$className = $class;
		if (is_object($class)) $className = $class->getName();
		if (isset($this->_parameters[$className]) && isset($this->_parameters[$className][$method])) {
			return $this->_parameters[$className][$method];
		}
		$reflectMethod = $this->getReflectMethod($class, $method);
		if (!($reflectMethod instanceof ReflectionMethod)) {
			throw new ReflectionException("Class does not have a function $className::$method");
		}
		$className = $reflectMethod->getDeclaringClass()->getName();
		if (isset($this->_parameters[$className]) && isset($this->_parameters[$className][$reflectMethod->getName()])) {
			return $this->_parameters[$className][$reflectMethod->getName()];
		}
		return $this->setParameters($className, $reflectMethod->getName(), $this->resolveMethodParameters($reflectMethod));
	}


	/**
	 * @param $class
	 * @param $method
	 * @param $parameters
	 */
	private function setParameters($class, $method, $parameters)
	{
		if (!isset($this->_parameters[$class])) {
			$this->_parameters[$class] = [];
		}
		if (!isset($this->_parameters[$class][$method])) {
			$this->_parameters[$class][$method] = [];
		}
		return $this->_parameters[$class][$method] = $parameters;
	}


	/**
	 * @param \Closure $reflectionMethod
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public function resolveFunctionParameters(\Closure $reflectionMethod): array
	{
		return $this->resolveMethodParameters(new ReflectionFunction($reflectionMethod));
	}


	/**
	 * @param ReflectionMethod|ReflectionFunction $reflectionMethod
	 * @return array
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function resolveMethodParameters(ReflectionMethod|ReflectionFunction $reflectionMethod): array
	{
		if ($reflectionMethod->getNumberOfParameters() < 1) {
			return [];
		}
		$params = [];
		foreach ($reflectionMethod->getParameters() as $key => $parameter) {
			if ($parameter->isDefaultValueAvailable()) {
				$params[$key] = $parameter->getDefaultValue();
			} else if ($parameter->getType() === null) {
				$params[$key] = $parameter->getType();
			} else {
				$type = $parameter->getType()->getName();
				if (is_string($type) && class_exists($type)) {
					$type = Snowflake::getDi()->get($type);
				}
				$params[$key] = match ($parameter->getType()) {
					'string' => '',
					'int', 'float' => 0,
					'', null, 'object', 'mixed' => NULL,
					'bool' => false,
					default => $type
				};
			}
		}
		return $params;
	}


	/**
	 * @param $class
	 * @return ReflectionClass|null
	 * @throws ReflectionException
	 */
	public function getReflect($class): ?ReflectionClass
	{
		if (!isset($this->_reflection[$class])) {
			return $this->resolveDependencies($class);
		}
		return $this->_reflection[$class];
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
	 * @param $old
	 * @param $newParam
	 *
	 * @return mixed
	 */
	private function mergeParam($old, $newParam): array
	{
		if (empty($old)) {
			return $newParam;
		} else if (empty($newParam)) {
			return $old;
		}
		foreach ($newParam as $key => $val) {
			$old[$key] = $val;
		}
		return $old;
	}
}
