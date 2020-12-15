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

	use \Annotation\Route\Node;

	/**
	 * Route constructor.
	 * @param string $uri
	 * @param string $method
	 * @param array|null $middleware
	 * @param array|null $interceptor
	 * @param array|null $limits
	 * @param array|null $after
	 */
	public function __construct(
		public string $uri,
		public string $method,
		public ?array $middleware = null,
		public ?array $interceptor = null,
		public ?array $limits = null,
		public ?array $after = null
	)
	{
	}


	/**
	 * @param array|Closure $handler
	 * @param array $attributes
	 * @return ?Node
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function setHandler(array|Closure $handler, array $attributes): ?Node
	{
		$router = Snowflake::app()->getRouter();
		// TODO: Implement setHandler() method.

		$node = $router->addRoute($this->uri, $handler, $this->method);

		return $this->add($node);
	}


}
