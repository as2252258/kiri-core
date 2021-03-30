<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use HttpServer\Route\Router;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

#[\Attribute(\Attribute::TARGET_METHOD)] class Route extends Attribute
{

	/**
	 * Route constructor.
	 * @param string $uri
	 * @param string $method
	 * @param string $version
	 */
	public function __construct(
		public string $uri,
		public string $method,
		public string $version = 'v.1.0'
	)
	{
	}


	/**
	 * @param array $handler
	 * @return Router
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function execute(array $handler): Router
	{
		// TODO: Implement setHandler() method.
		$router = Snowflake::app()->getRouter();

		$router->addRoute($this->uri, $handler, $this->method);

		return $router;
	}


}
