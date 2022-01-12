<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/4/24 0024
 * Time: 17:27
 */
declare(strict_types=1);

namespace Kiri\Di;

use Closure;
use Exception;
use Kiri;
use Kiri\Abstracts\Logger;
use Kiri\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Class Container
 * @package Kiri\Di
 */
class Container implements ContainerInterface
{

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


	/** @var array|string[] */
	private array $_interfaces = [
		LoggerInterface::class => Logger::class
	];


	/**
	 * @param string $id
	 * @return mixed
	 * @throws
	 */
	public function get(string $id): mixed
	{
		if ($id == ContainerInterface::class) {
			return $this;
		}
		return $this->make($id, [], []);
	}


	/**
	 * @param $class
	 * @param array $constrict
	 * @param array $config
	 * @return mixed
	 * @throws
	 */
	public function make($class, array $constrict = [], array $config = []): mixed
	{
		if ($class == ContainerInterface::class) {
			return $this;
		}
		if ($this->isInterface($class)) {
			$class = $this->_interfaces[$class];
		}
		if (!isset($this->_singletons[$class])) {
			$this->_singletons[$class] = $this->resolve($class, $constrict, $config);
		}
		return $this->_singletons[$class];
	}


	/**
	 * @param string $interface
	 * @param string $class
	 */
	public function mapping(string $interface, string $class)
	{
		$this->_interfaces[$interface] = $class;
	}


	/**
	 * @param $class
	 * @return bool
	 */
	public function isInterface($class): bool
	{
		$reflect = $this->getReflect($class);
		if ($reflect->isInterface()) {
			return true;
		}
		return false;
	}


	/**
	 * @param string $interface
	 * @param $object
	 */
	public function setBindings(string $interface, $object)
	{
		if (is_string($object)) {
			$this->_interfaces[$interface] = $object;
		} else {
			$className = get_class($object);
			$this->_interfaces[$interface] = $className;
			$this->_singletons[$className] = $object;
		}
	}


	/**
	 * @param $class
	 * @param array $constrict
	 * @param array $config
	 * @return object
	 * @throws
	 */
	public function create($class, array $constrict = [], array $config = []): object
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
		foreach (NoteManager::getPropertyAnnotation($reflect) as $property => $inject) {
			/** @var Inject $inject */
			$inject->execute($object, $property);
		}
		return $object;
	}


	/**
	 * @param $className
	 * @param $method
	 * @return array
	 */
	public function getMethodAttribute($className, $method = null): array
	{
		$methods = NoteManager::getMethodAnnotation($this->getReflect($className));
		if (!empty($method)) {
			return $methods[$method] ?? [];
		}
		return $methods;
	}


	/**
	 * @param string $class
	 * @param string|null $property
	 * @return ReflectionProperty|ReflectionProperty[]|null
	 */
	public function getClassReflectionProperty(string $class, string $property = null): ReflectionProperty|null|array
	{
		$lists = NoteManager::getProperty($this->getReflect($class));
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
		Kiri::configure($object, $config);
		if (method_exists($object, 'init') && is_callable([$object, 'init'])) {
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
		if (isset($this->_reflection[$class])) {
			return $this->_reflection[$class];
		}
		$reflect = new ReflectionClass($class);
		if ($reflect->isAbstract() || $reflect->isTrait() || $reflect->isInterface()) {
			return $this->_reflection[$class] = $reflect;
		}
		$construct = NoteManager::resolveTarget($reflect);
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
		return NoteManager::getMethods($class);
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
	 * @param string $className
	 * @param string $method
	 * @return array|null
	 * @throws ReflectionException
	 */
	public function getMethodParameters(string $className, string $method): ?array
	{
		if (isset($this->_parameters[$className]) && isset($this->_parameters[$className][$method])) {
			return $this->_parameters[$className][$method];
		}
		$reflectMethod = $this->getReflectMethod($this->getReflect($className), $method);
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
	 * @return mixed
	 */
	private function setParameters($class, $method, $parameters): mixed
	{
		if (!isset($this->_parameters[$class])) {
			$this->_parameters[$class] = [];
		}
		return $this->_parameters[$class][$method] = $parameters;
	}


	/**
	 * @param Closure $reflectionMethod
	 * @return array
	 * @throws ReflectionException
	 */
	public function getFunctionParameters(Closure $reflectionMethod): array
	{
		return $this->resolveMethodParameters(new ReflectionFunction($reflectionMethod));
	}


	/**
	 * @param ReflectionMethod|ReflectionFunction $reflectionMethod
	 * @return array
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
				if (is_string($type) && class_exists($type) || isset($this->_interfaces[$type])) {
					$type = Kiri::getDi()->get($type);
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
			$this->_reflection[$class], $this->_singletons[$class], $this->_constructs[$class]
		);
	}

	/**
	 * @return $this
	 */
	public function flush(): static
	{
		$this->_reflection = [];
		$this->_singletons = [];
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

	/**
	 * @param string $id
	 * @return bool
	 */
	public function has(string $id): bool
	{
		return isset($this->_singletons[$id]) || isset($this->_interfaces[$id]);
	}
}
