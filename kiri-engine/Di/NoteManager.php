<?php

namespace Kiri\Di;

use JetBrains\PhpStorm\Pure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;

class AnnotationManager
{


	private static array $_classTarget = [];
	private static array $_classMethodAnnotation = [];
	private static array $_classMethod = [];
	private static array $_classPropertyAnnotation = [];
	private static array $_classProperty = [];
	private static array $_mapping = [];


	/**
	 * @return void
	 */
	public static function clear()
	{
		static::$_classTarget = [];
		static::$_classMethodAnnotation = [];
		static::$_classMethod = [];
		static::$_classPropertyAnnotation = [];
		static::$_classProperty = [];
		static::$_mapping = [];
	}


	/**
	 * @param ReflectionClass $class
	 */
	public static function setTargetAnnotation(ReflectionClass $class)
	{
		$className = $class->getName();
		if (!isset(static::$_classTarget[$className])) {
			static::$_classTarget[$className] = [];
		}
		foreach ($class->getAttributes() as $attribute) {
			if (!class_exists($attribute->getName())) {
				continue;
			}

			$instance = $attribute->newInstance();

			static::$_classTarget[$className][] = $instance;

			self::setMappingClass($attribute, $className);
		}
	}


	/**
	 * @param ReflectionAttribute $attribute
	 * @param string $class
	 */
	public static function setMappingClass(ReflectionAttribute $attribute, string $class)
	{
		if (!isset(static::$_mapping[$attribute->getName()])) {
			static::$_mapping[$attribute->getName()] = [];
		}
		if (!isset(static::$_mapping[$attribute->getName()][$class])) {
			static::$_mapping[$attribute->getName()][$class] = [];
		}
	}


	/**
	 * @param ReflectionAttribute $attribute
	 * @param string $class
	 * @param string $method
	 * @param mixed $instance
	 */
	public static function setMappingMethod(ReflectionAttribute $attribute, string $class, string $method, mixed $instance)
	{
		self::setMappingClass($attribute, $class);

		if (!isset(static::$_mapping[$attribute->getName()][$class]['method'])) {
			static::$_mapping[$attribute->getName()][$class]['method'] = [];
		}
		static::$_mapping[$attribute->getName()][$class]['method'][] = [$method => $instance];
	}


	/**
	 * @param ReflectionAttribute $attribute
	 * @param string $class
	 * @param string $property
	 * @param $instance
	 */
	public static function setMappingProperty(ReflectionAttribute $attribute, string $class, string $property, $instance)
	{
		self::setMappingClass($attribute, $class);

		$mapping = static::$_mapping[$attribute->getName()][$class];
		if (!isset($mapping['property'])) {
			$mapping['property'] = [];
		}
		$mapping['property'][] = [$property => $instance];
		static::$_mapping[$attribute->getName()][$class] = $mapping;
	}


	/**
	 * @param mixed $class
	 * @return array
	 */
	public static function getTargetAnnotation(mixed $class): array
	{
		if (!is_string($class)) {
			$class = $class::class;
		}
		return static::$_classTarget[$class] ?? [];
	}


	/**
	 * @param ReflectionClass $class
	 */
	public static function setMethodAnnotation(ReflectionClass $class)
	{
		$className = $class->getName();
		static::$_classMethodAnnotation[$className] = static::$_classMethod[$className] = [];
		foreach ($class->getMethods() as $ReflectionMethod) {
			static::$_classMethod[$className][$ReflectionMethod->getName()] = $ReflectionMethod;
			static::$_classMethodAnnotation[$className][$ReflectionMethod->getName()] = [];
			foreach ($ReflectionMethod->getAttributes() as $attribute) {
				if (!class_exists($attribute->getName())) {
					continue;
				}
				$instance = $attribute->newInstance();

				static::$_classMethodAnnotation[$className][$ReflectionMethod->getName()][] = $instance;

				self::setMappingMethod($attribute, $className, $ReflectionMethod->getName(), $instance);
			}
		}
	}


	/**
	 * @param string $class
	 * @param string $method
	 * @return bool
	 */
	public static function hasMethod(string $class, string $method): bool
	{
		return isset(static::$_classMethod[$class]) && isset(static::$_classMethod[$class][$method]);
	}


	/**
	 * @param ReflectionClass $class
	 * @return array
	 */
	#[Pure] public static function getMethodAnnotation(ReflectionClass $class): array
	{
		return static::$_classMethodAnnotation[$class->getName()] ?? [];
	}


	/**
	 * @param \ReflectionClass $reflect
	 * @return \ReflectionMethod|null
	 */
	public static function resolveTarget(ReflectionClass $reflect): ?\ReflectionMethod
	{
		AnnotationManager::setPropertyAnnotation($reflect);
		AnnotationManager::setTargetAnnotation($reflect);
		AnnotationManager::setMethodAnnotation($reflect);

		return $reflect->getConstructor();
	}


	/**
	 * @param ReflectionClass $class
	 */
	public static function setPropertyAnnotation(ReflectionClass $class)
	{
		$className = $class->getName();
		static::$_classProperty[$className] = static::$_classPropertyAnnotation[$className] = [];
		foreach ($class->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PUBLIC |
			ReflectionProperty::IS_PROTECTED) as $ReflectionMethod) {
			static::$_classProperty[$className][$ReflectionMethod->getName()] = $ReflectionMethod;
			foreach ($ReflectionMethod->getAttributes() as $attribute) {
				if (!class_exists($attribute->getName())) {
					continue;
				}

				$instance = $attribute->newInstance();

				static::$_classPropertyAnnotation[$className][$ReflectionMethod->getName()] = $instance;

				self::setMappingProperty($attribute, $className, $ReflectionMethod->getName(), $instance);
			}
		}
	}


	/**
	 * @param string $attribute
	 * @param string|null $class
	 * @return array[]
	 */
	public static function getAttributeTrees(string $attribute, string $class = null): array
	{
		$mapping = static::$_mapping[$attribute] ?? [];
		if (empty($mapping) || empty($class)) {
			return $mapping;
		}
		return $mapping[$class] ?? [];
	}


	/**
	 * @param string $attribute
	 * @param string $class
	 * @param string|null $method
	 * @return array
	 */
	public static function getSpecify_annotation(string $attribute, string $class, string $method = null): mixed
	{
		$class = self::getAttributeTrees($attribute, $class);
		if (empty($class) || !isset($class['method'])) {
			return null;
		}
		if (empty($method)) {
			return $class['method'];
		}
		foreach ($class['method'] as $value) {
			$key = key($value);
			if ($method == $key) {
				return $value[$key];
			}
		}
		return null;
	}


	/**
	 * @param string $attribute
	 * @param string $class
	 * @param string $method
	 * @return mixed
	 */
	public static function getPropertyByAnnotation(string $attribute, string $class, string $method): mixed
	{
		$class = self::getAttributeTrees($attribute, $class);
		if (empty($class) || !isset($class['property'])) {
			return [];
		}
		foreach ($class['property'] as $value) {
			$key = key($value);
			if ($method == $key) {
				return $value[$key];
			}
		}
		return null;
	}


	/**
	 * @param ReflectionClass|string $class
	 * @return array
	 * @throws \ReflectionException
	 */
	public static function getMethods(ReflectionClass|string $class): array
	{
		if (is_string($class)) {
			$class = self::getReflect($class);
		}
		return static::$_classMethod[$class->getName()] ?? [];
	}


	/**
	 * @param ReflectionClass $class
	 * @return ReflectionProperty[]
	 */
	#[Pure] public static function getProperty(ReflectionClass $class): array
	{
		return static::$_classProperty[$class->getName()] ?? [];
	}


	/**
	 * @param ReflectionClass $class
	 * @return array
	 */
	#[Pure] public static function getPropertyAnnotation(ReflectionClass $class): array
	{
		return static::$_classPropertyAnnotation[$class->getName()] ?? [];
	}


}
