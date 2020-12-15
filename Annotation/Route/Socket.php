<?php


namespace Annotation\Route;


use Annotation\IAnnotation;
use Closure;
use Exception;
use HttpServer\Route\Node;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

/**
 * Class Socket
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Socket implements IAnnotation
{

	const CLOSE = 'CLOSE';
	const MESSAGE = 'MESSAGE';
	const HANDSHAKE = 'HANDSHAKE';

	use \Annotation\Route\Node;


	/**
	 * Socket constructor.
	 * @param string $event
	 * @param string|null $uri
	 * @param array|null $middleware
	 * @param array|null $interceptor
	 * @param array|null $limits
	 * @param array|null $after
	 */
	public function __construct(
		public string $event,
		public ?string $uri = null,
		public ?array $middleware = null,
		public ?array $interceptor = null,
		public ?array $limits = null,
		public ?array $after = null
	)
	{
	}


	/**
	 * @param array|Closure $handler
	 * @return Node|null
	 * @throws ComponentException
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function setHandler(array|Closure $handler): ?Node
	{
		$router = Snowflake::app()->getRouter();
		// TODO: Implement setHandler() method.

		$method = $this->event . '::' . (is_null($this->uri) ? 'event' : $this->uri);

		$node = $router->addRoute($method, $handler, 'sw::socket');

		return $this->add($node);
	}

}
