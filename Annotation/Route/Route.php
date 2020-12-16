<?php


namespace Annotation\Route;


use Closure;
use Exception;
use HttpServer\Route\Node;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Annotation\IAnnotation;

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
	 * @param array|Closure $handler
	 * @return ?Node
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function setHandler(array|Closure $handler): ?Node
	{
		$router = Snowflake::app()->getRouter();
		// TODO: Implement setHandler() method.

		return $router->addRoute($this->uri, $handler, $this->method);
	}


}
