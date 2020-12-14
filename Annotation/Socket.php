<?php


namespace Annotation;


use Closure;
use HttpServer\Route\Node;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;

/**
 * Class Socket
 * @package Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class Socket
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
		public ?string $uri
	)
	{
	}


	/**
	 * @param Closure|array $closure
	 * @return mixed
	 * @throws ComponentException
	 */
	public function setHandler(Closure|array $closure): mixed
	{
		$router = Snowflake::app()->getRouter();
		// TODO: Implement setHandler() method.

		$method = $this->event . '::' . ($this->uri ?? 'event');

		return $router->addRoute($method, $closure, 'sw::socket');
	}

}
