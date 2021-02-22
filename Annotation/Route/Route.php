<?php


namespace Annotation\Route;


use Closure;
use Exception;
use HttpServer\Route\Node;
use HttpServer\Route\Router;
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
	 * @param array $handler
	 * @return Router
	 * @throws ComponentException
	 * @throws ConfigException
	 */
	public function execute(array $handler): Router
	{
		// TODO: Implement setHandler() method.
		$router = Snowflake::app()->getRouter();

		$router->addRoute($this->uri, $handler, $this->method);

		return $router;
	}


}
