<?php


namespace Annotation;


use Closure;
use HttpServer\Route\Node;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;

#[\Attribute(\Attribute::TARGET_METHOD)] class Route implements IAnnotation
{


	/**
	 * Route constructor.
	 * @param string $uri
	 * @param string $method
	 * @param string|array $middleware
	 * @param string|array $interceptor
	 * @param string|array $limits
	 * @param string|array $after
	 */
	public function __construct(
		public string $uri,
		public string $method,
		public string|array $middleware,
		public string|array $interceptor,
		public string|array $limits,
		public string|array $after
	)
	{
	}


	/**
	 * @param array|Closure $handler
	 * @param array $attributes
	 * @return ?Node
	 * @throws ComponentException
	 * @throws ConfigException
	 */
	public function setHandler(array|Closure $handler, array $attributes): ?Node
	{
		$router = Snowflake::app()->getRouter();
		// TODO: Implement setHandler() method.

		$node = $router->addRoute($this->uri, $handler, $this->method);
		foreach ($attributes as $name => $attribute) {
			$first = 'add' . ucfirst($attribute);

			$_handler = is_array($handler) ? $handler[0] : $handler;
			if (!method_exists($_handler, $first)) {
				continue;
			}
			$node->$first($attribute);
		}
		return $node;
	}


}
