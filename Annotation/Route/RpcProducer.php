<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use HttpServer\Http\Request;
use HttpServer\Route\Router;
use JetBrains\PhpStorm\Pure;
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
	#[Pure] public function __construct(public string $cmd, public string $protocol = self::PROTOCOL_SERIALIZE, public int $port = 443)
	{
		$this->uri = 'rpc/p' . $this->port . '/' . ltrim($this->cmd, '/');
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

		$router->addRoute($this->uri, [$class, $method], Request::HTTP_CMD)
			->setDataType($this->protocol);

		return $router;
	}


}
