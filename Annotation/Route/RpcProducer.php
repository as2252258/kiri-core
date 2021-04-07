<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use HttpServer\Http\Request;
use HttpServer\Route\Router;
use JetBrains\PhpStorm\Pure;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;


/**
 * Class RpcProducer
 * @package Annotation\Route
 */
#[\Attribute(\Attribute::TARGET_METHOD)] class RpcProducer extends Attribute
{

	private string $uri = '';

	const PROTOCOL_JSON = 'json';
	const PROTOCOL_SERIALIZE = 'serialize';


	/**
	 * Route constructor.
	 * @param string $cmd
	 * @param string $protocol
	 * @param int $port
	 */
	#[Pure] public function __construct(public string $cmd, public string $protocol, public int $port)
	{
		$this->uri = 'rpc/p' . $this->port . '/' . ltrim($this->cmd, '/');
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

		$router->addRoute($this->uri, $handler, Request::HTTP_CMD);

		return $router;
	}


}
