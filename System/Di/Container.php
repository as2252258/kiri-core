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
use Annotation\Target;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use Snowflake\Abstracts\BaseObject;
use Snowflake\Abstracts\Configure;
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
	private static array $_singletons = [];

	/**
	 * @var array
	 *
	 * class new instance construct parameter
	 */
	private static array $_constructs = [];

	/**
	 * @var array
	 *
	 * implements \ReflectClass
	 */
	private static array $_reflection = [];


	/**
	 * @var ReflectionProperty[]
	 */
	private static array $_reflectionProperty = [];

	/**
	 * @var ReflectionMethod[]
	 */
	private static array $_reflectionMethod = [];

	/**
	 * @var ReflectionClass[]
	 */
	private static array $_reflectionClass = [];


	/**
	 * @var array
	 */
	private static array $_propertyAttributes = [];


	/**
	 * @var array
	 */
	private static array $_methodsAttributes = [];


	/**
	 * @var array
	 */
	private static array $_targetAttributes = [];


	private static array $_attributeMapping = [];


	/**
	 * @var array
	 *
	 * The construct parameter
	 */
	private static array $_param = [];

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
		if (isset(static::$_singletons[$class])) {
			return static::$_singletons[$class];
		} else if (!isset(static::$_constructs[$class])) {
			return $this->resolve($class, $constrict, $config);
		}
		$definition = static::$_constructs[$class];
		if (is_callable($definition, TRUE)) {
			return call_user_func($definition, $this, $constrict, $config);
		} else if (is_array($definition)) {
			return static::$_singletons[$class] = $this->resolveDefinition($definition, $class, $config, $constrict);
		} else if (is_object($definition)) {
			return static::$_singletons[$class] = $definition;
		} else {
			throw new NotFindClassException($class);
		}
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
		[$reflect, $dependencies] = $this->resolveDependencies($class, $constrict);
		if (empty($reflect) || !$reflect->isInstantiable()) {
			throw new NotFindClassException($class);
		}
		$object = $this->setConfig($config, $reflect, $dependencies, $class);

		return $this->setClassAnnotation($reflect, $object);
	}


	/**
	 * @param $reflect
	 * @param $object
	 * @return mixed
	 * @throws Exception
	 */
	private function setClassAnnotation($reflect, $object): mixed
	{
		static::$_reflectionClass[$reflect->getName()] = [];
		foreach ($reflect->getAttributes() as $attribute) {
			static::$_reflectionClass[$reflect->getName()][] = $attribute->newInstance();
		}
		return $this->propertyInject($reflect, $object);
	}


	/**
	 * @param $config
	 * @param $reflect
	 * @param $dependencies
	 * @param $class
	 * @return mixed
	 */
	private function setConfig($config, $reflect, $dependencies, $class): mixed
	{
		if (empty($config) || !is_array($config)) {
			$object = $this->newInstance($reflect, $dependencies);
		} else if (!empty($dependencies) && $reflect->implementsInterface(Configure::class)) {
			$dependencies[count($dependencies) - 1] = $config;
			$object = $this->newInstance($reflect, $dependencies);
		} else {
			static::$_param[$class] = $config;

			$object = $this->onAfterInit($this->newInstance($reflect, $dependencies), $config);
		}
		return $object;
	}


	/**
	 * @param $reflect
	 * @param $dependencies
	 * @return mixed
	 */
	private function newInstance($reflect, $dependencies): mixed
	{
		if (!empty($dependencies)) {
			return $reflect->newInstanceArgs($dependencies);
		}
		return $reflect->newInstance();
	}


	/**
	 * @param ReflectionClass $reflect
	 * @param $object
	 * @return mixed
	 * @throws Exception
	 */
	public function propertyInject(ReflectionClass $reflect, $object): mixed
	{
		if (!isset(static::$_propertyAttributes[$reflect->getName()])) {
			return $object;
		}
		foreach (static::$_propertyAttributes[$reflect->getName()] as $property => $inject) {
			/** @var Inject $inject */
			$inject->execute($object, $property);
		}
		return $object;
	}


	/**
	 * @param ReflectionClass $class
	 */
	private function resolveMethodAttribute(ReflectionClass $class)
	{
		if ($class->isAbstract() || $class->isTrait()) {
			return;
		}
		foreach ($class->getMethods() as $method) {
			if ($method->isStatic()) {
				continue;
			}
			$this->setReflectionMethod($class, $method);
			foreach ($method->getAttributes() as $attribute) {
				if (!class_exists($attribute->getName())) {
					continue;
				}
				$this->setMethodsAttributes($class, $method, $attribute->newInstance());
			}
		}
	}


	/**
	 * @param ReflectionClass $class
	 * @param ReflectionMethod $method
	 */
	private function setReflectionMethod(ReflectionClass $class, ReflectionMethod $method)
	{
		if (!isset(static::$_attributeMapping[$class->getName()])) {
			static::$_attributeMapping[$class->getName()] = [];
		}
		static::$_attributeMapping[$class->getName()][$method->getName()][] = $method;
	}


	/**
	 * @param ReflectionClass $attribute
	 * @param ReflectionMethod $method
	 * @param $object
	 */
	private function setMethodsAttributes(ReflectionClass $attribute, ReflectionMethod $method, $object)
	{
		if (!isset(static::$_methodsAttributes[$attribute->getName()])) {
			static::$_methodsAttributes[$attribute->getName()] = [];
		}
		static::$_methodsAttributes[$attribute->getName()][$method->getName()][] = $object;
	}


	/**
	 * @param ReflectionClass $class
	 */
	private function resolveTargetAttribute(ReflectionClass $class)
	{
		if ($class->isAbstract() || $class->isTrait()) {
			return;
		}
		foreach ($class->getAttributes() as $method) {
			if ($method->getName() == Target::class) {
				continue;
			}
			static::$_targetAttributes[$class->getName()] = $method->newInstance();
		}
	}


	/**
	 * @param $className
	 * @param $method
	 * @return ReflectionMethod|null
	 */
	public function getReflectionMethod($className, $method): ?ReflectionMethod
	{
		return static::$_reflectionMethod[$className][$method] ?? null;
	}


	/**
	 * @param $className
	 * @return ReflectionMethod|null
	 */
	public function getTargetAnnotation($className): ?ReflectionMethod
	{
		return static::$_targetAttributes[$className] ?? null;
	}


	/**
	 * @param $className
	 * @param $method
	 * @return array
	 */
	public function getMethodAttribute($className, $method = null): array
	{
		$methods = static::$_methodsAttributes[$className] ?? [];
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
		if (!isset(static::$_reflectionProperty[$class])) {
			return null;
		}
		$properties = static::$_reflectionProperty[$class];
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
	 * @param array $dependencies
	 * @return array|null
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	private function resolveDependencies($class, array $dependencies = []): ?array
	{
		if (!isset(static::$_reflection[$class])) {
			if (!($reflect = new ReflectionClass($class))->isInstantiable()) {
				throw new ReflectionException('Class ' . $class . ' cannot be instantiated');
			}
			static::$_reflection[$class] = $reflect;
			$this->resolveTargetAttribute(static::$_reflection[$class]);
			$this->resolveMethodAttribute(static::$_reflection[$class]);
			$this->scanProperty(static::$_reflection[$class]);
		}
		if (!is_null($construct = static::$_reflection[$class]->getConstructor())) {
			foreach ($this->resolveMethodParam($construct) as $key => $item) {
				$dependencies[$key] = $item;
			}
		}
		return [static::$_reflection[$class], $dependencies];
	}


	/**
	 * @param ReflectionClass $reflectionClass
	 * @return void
	 */
	private function scanProperty(ReflectionClass $reflectionClass): void
	{
		$properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC |
			ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED
		);

		$className = $reflectionClass->getName();
		foreach ($properties as $property) {
			$targets = $property->getAttributes(Inject::class);
			if (count($targets) < 1) {
				continue;
			}

			static::$_reflectionProperty[$className][$property->getName()] = $property;

			static::$_propertyAttributes[$className][$property->getName()] = $targets[0]->newInstance();
		}
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
		foreach ($method->getParameters() as $parameter) {
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
		$reflect = static::$_reflection[$class] ?? null;
		if (!is_null($reflect)) {
			return $reflect;
		}
		return $this->resolveDependencies($class)[0];
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
			static::$_reflection[$class], static::$_singletons[$class],
			static::$_param[$class], static::$_constructs[$class]
		);
	}

	/**
	 * @return $this
	 */
	public function flush(): static
	{
		static::$_reflection = [];
		static::$_singletons = [];
		static::$_param = [];
		static::$_constructs = [];
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
		if (empty(static::$_param[$class])) {
			return $newParam;
		} else if (empty($newParam)) {
			return static::$_param[$class];
		}
		$old = static::$_param[$class];
		foreach ($newParam as $key => $val) {
			$old[$key] = $val;
		}
		return $old;
	}
}
