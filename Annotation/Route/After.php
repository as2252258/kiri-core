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
#[\Attribute(\Attribute::TARGET_METHOD)] class After implements IAnnotation
{


	/**
	 * Interceptor constructor.
	 * @param \HttpServer\IInterface\After|\HttpServer\IInterface\After[] $after
	 * @throws
	 */
	public function __construct(public string|array $after)
	{
	}


	/**
	 * @param array $handler
	 * @return After
	 */
	public function execute(array $handler): static
	{
		return $this;
	}


}
