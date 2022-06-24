<?php


namespace Kiri\Annotation;


use ReturnTypeWillChange;

/**
 * Class Attribute
 * @package Annotation
 */
abstract class AbstractAttribute implements IAnnotation
{


	protected object $class;


	protected string $method;


	/**
	 * @param static $class
	 * @param mixed|string $method
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function execute(mixed $class, mixed $method = ''): mixed
	{
		// TODO: Implement execute() method.
		return true;
	}

	/**
	 * @return object
	 */
	public function getClass(): object
	{
		return $this->class;
	}

	/**
	 * @param object $class
	 */
	public function setClass(object $class): void
	{
		$this->class = $class;
	}

	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * @param string $method
	 */
	public function setMethod(string $method): void
	{
		$this->method = $method;
	}


}
