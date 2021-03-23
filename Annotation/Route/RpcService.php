<?php


namespace Annotation\Route;


use Annotation\Attribute;
use Exception;
use HttpServer\Http\Request;
use HttpServer\Route\Router;
use JetBrains\PhpStorm\Pure;
use ReflectionException;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;

#[\Attribute(\Attribute::TARGET_METHOD)] class RpcService extends Attribute
{

	private string $uri = '';


	/**
	 * Route constructor.
	 * @param string $cmd
	 * @param int $port
	 */
	#[Pure] public function __construct(public string $cmd, public int $port)
	{
		$this->uri = 'rpc/' . $this->port . '/' . ltrim($this->cmd, '/');
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
