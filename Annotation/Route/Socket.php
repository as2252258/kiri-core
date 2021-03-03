<?php


namespace Annotation\Route;


use Annotation\IAnnotation;
use Closure;
use Exception;
use HttpServer\Route\Node;
use HttpServer\Route\Router;
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

	/**
	 * Socket constructor.
	 * @param string $event
	 * @param string|null $uri
	 */
	public function __construct(
		public string $event,
		public ?string $uri = null
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

		$method = $this->event . '::' . (is_null($this->uri) ? 'event' : $this->uri);

		$router->addRoute($method, $handler, 'sw::socket');

		return $router;
	}

}
