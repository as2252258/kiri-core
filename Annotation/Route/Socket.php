<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use HttpServer\Route\Router;
use Kiri\Kiri;

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
	public function __construct(public string $event, public ?string $uri = null, public string $version = 'v.1.0')
	{
	}


	/**
	 * @param mixed $class
	 * @param mixed|null $method
	 * @return Router
	 * @throws Exception
	 */
	public function execute(mixed $class, mixed $method = null): Router
	{
		// TODO: Implement setHandler() method.
		$router = Kiri::app()->getRouter();

		$path = $this->event . '::' . (is_null($this->uri) ? 'event' : $this->uri);

		$router->addRoute($path, [di($class), $method], 'sw::socket');

		return $router;
	}

}
