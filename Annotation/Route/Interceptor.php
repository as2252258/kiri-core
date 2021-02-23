<?php


namespace Annotation\Route;


use Annotation\IAnnotation;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Interceptor
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Interceptor implements IAnnotation
{


	/**
	 * Interceptor constructor.
	 * @param string|array $interceptor
	 * @throws
	 */
	public function __construct(public string|array $interceptor)
	{
		if (!is_string($this->interceptor)) {
			return;
		}
		$this->interceptor = [$this->interceptor];
	}


	/**
	 * @param array $handler
	 * @return Interceptor
	 */
	public function execute(array $handler): static
	{
		return $this;
	}

}
