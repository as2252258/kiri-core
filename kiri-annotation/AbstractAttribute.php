<?php


namespace Kiri\Annotation;


use ReturnTypeWillChange;

/**
 * Class Attribute
 * @package Annotation
 */
abstract class AbstractAttribute implements IAnnotation
{


	protected object $_class;


	protected string $_method;


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
		return $this->_class;
	}

	/**
	 * @param object $class
	 */
	public function setClass(object $class): void
	{
		$this->_class = $class;
	}

	/**
	 * @return string
	 */
	public function getMethod(): string
	{
		return $this->_method;
	}

	/**
	 * @param string $method
	 */
	public function setMethod(string $method): void
	{
		$this->_method = $method;
	}


}
