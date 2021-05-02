<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
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
#[\Attribute(\Attribute::TARGET_METHOD)] class Socket extends Attribute
{

	const CLOSE = 'CLOSE';
	const MESSAGE = 'MESSAGE';
	const HANDSHAKE = 'HANDSHAKE';

	/**
	 * Socket constructor.
	 * @param string $event
	 * @param string|null $uri
	 * @param string $version
	 */
	public function __construct(
		public string $event,
		public ?string $uri = null,
		public string $version = 'v.1.0'
	)
	{
	}


	/**
	 * @param array $handler
	 * @return Router
	 * @throws Exception
	 */
    public function execute(mixed $class, mixed $method = null): Router
	{
		// TODO: Implement setHandler() method.
		$router = Snowflake::app()->getRouter();

		$path = $this->event . '::' . (is_null($this->uri) ? 'event' : $this->uri);

		$router->addRoute($path, [$class, $method], 'sw::socket');

		return $router;
	}

}
