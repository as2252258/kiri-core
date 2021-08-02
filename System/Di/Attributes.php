<?php

namespace Snowflake\Di;

use JetBrains\PhpStorm\Pure;
use ReflectionClass;
use ReflectionMethod;

trait Attributes
{


	private array $_classTarget = [];
	private array $_classMethodNote = [];
	private array $_classMethod = [];
	private array $_classPropertyNote = [];
	private array $_classProperty = [];


	/**
	 * @param ReflectionClass $class
	 */
	protected function setTargetNote(ReflectionClass $class)
	{
		$className = $class->getName();
		if (!isset($this->_classTarget[$className])) {
			$this->_classTarget[$className] = [];
		}
		foreach ($class->getAttributes() as $attribute) {
			if (!class_exists($attribute->getName())) {
				continue;
			}
			$this->_classTarget[$className][] = $attribute->newInstance();
		}
	}


	/**
	 * @param mixed $class
	 * @return array
	 */
	public function getTargetNote(mixed $class): array
	{
		if (!is_string($class)) {
			$class = $class::class;
		}
		return $this->_classTarget[$class] ?? [];
	}


	/**
	 * @param ReflectionClass $class
	 */
	protected function setMethodNote(ReflectionClass $class)
	{
		$className = $class->getName();
		$this->_classMethodNote[$className] = $this->_classMethod[$className] = [];
		foreach ($class->getMethods(ReflectionMethod::IS_FINAL | ReflectionMethod::IS_PRIVATE
			| ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_ABSTRACT
		) as $ReflectionMethod) {
			$this->_classMethod[$className][$ReflectionMethod->getName()] = $ReflectionMethod;
			foreach ($ReflectionMethod->getAttributes() as $attribute) {
				if (!class_exists($attribute->getName())) {
					continue;
				}
				$this->_classMethodNote[$className][] = $attribute->newInstance();
			}
		}
	}


	/**
	 * @param ReflectionClass $class
	 * @return array
	 */
	#[Pure] public function getMethodNote(ReflectionClass $class): array
	{
		return $this->_classMethodNote[$class->getName()] ?? [];
	}


	/**
	 * @param ReflectionClass $class
	 */
	protected function setPropertyNote(ReflectionClass $class)
	{
		$className = $class->getName();
		$this->_classProperty[$className] = $this->_classPropertyNote[$className] = [];
		foreach ($class->getProperties(\ReflectionProperty::IS_PRIVATE | \ReflectionProperty::IS_PUBLIC |
			\ReflectionProperty::IS_PROTECTED) as $ReflectionMethod) {
			$this->_classProperty[$className][$ReflectionMethod->getName()] = $ReflectionMethod;
			foreach ($ReflectionMethod->getAttributes() as $attribute) {
				if (!class_exists($attribute->getName())) {
					continue;
				}
				$this->_classPropertyNote[$className][] = $attribute->newInstance();
			}
		}
	}


	/**
	 * @param ReflectionClass $class
	 * @return array
	 */
	#[Pure] public function getMethods(ReflectionClass $class): array
	{
		return $this->_classMethod[$class->getName()] ?? [];
	}


	/**
	 * @param ReflectionClass $class
	 * @return \ReflectionProperty[]
	 */
	#[Pure] public function getProperty(ReflectionClass $class): array
	{
		return $this->_classProperty[$class->getName()] ?? [];
	}


	/**
	 * @param ReflectionClass $class
	 * @return array
	 */
	#[Pure] public function getPropertyNote(ReflectionClass $class): array
	{
		return $this->_classPropertyNote[$class->getName()] ?? [];
	}


}
