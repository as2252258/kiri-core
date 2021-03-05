<?php


namespace Annotation\Route;


use Annotation\Attribute;
use HttpServer\Route\Router;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
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
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws ReflectionException
	 * @throws NotFindClassException
	 */
	public function execute(array $handler): Router
	{
		// TODO: Implement setHandler() method.
		$router = Snowflake::app()->getRouter();

		$node = $router->addRoute($this->uri, $handler, $this->method);
		$node::annotationInject($node, get_class($handler[0]), $handler[1]);
		return $router;
	}


}
