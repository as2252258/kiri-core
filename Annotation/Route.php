<?php


namespace Annotation;


use Closure;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

#[\Attribute(\Attribute::TARGET_METHOD)] class Route implements IAnnotation
{


	/**
	 * Route constructor.
	 * @param string $uri
	 * @param string $method
	 */
	public function __construct(
		public string $uri,
		public string $method
	)
	{
	}


	/**
	 * @param array|Closure $closure
	 * @return mixed
	 * @throws ComponentException
	 */
	public function setHandler(array|Closure $closure): mixed
	{
		$router = Snowflake::app()->getRouter();
		// TODO: Implement setHandler() method.

		return $router->addRoute($this->uri, $closure, $this->method);
	}


}
