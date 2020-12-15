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
