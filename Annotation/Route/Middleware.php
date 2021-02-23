<?php


namespace Annotation\Route;


use Annotation\IAnnotation;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Middleware
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Middleware implements IAnnotation
{


	/**
	 * Interceptor constructor.
	 * @param string|array $middleware
	 * @throws
	 */
	public function __construct(public string|array $middleware)
	{
		if (!is_string($this->middleware)) {
			return;
		}
		$this->middleware = [$this->middleware];
	}


	/**
	 * @param array $handler
	 * @return Middleware
	 */
	public function execute(array $handler): static
	{
		return $this;
	}


}
