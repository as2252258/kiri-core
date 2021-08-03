<?php

namespace Server;

use Exception;
use HttpServer\Http\Context;
use HttpServer\Http\Request as HSRequest;
use HttpServer\Route\Router;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Error;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\Server\Port;
use Throwable;


/**
 * Class HTTPServerListener
 * @package Server
 */
class HTTPServerListener extends Abstracts\Server
{

	protected static bool|Port $_http;

	use ListenerHelper;

	private Router $router;

	private \HttpServer\Http\Response $response;


	/**
	 * HTTPServerListener constructor.
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->router = Snowflake::getApp('router');
		$this->response = di(\HttpServer\Http\Response::class);
		parent::__construct();
	}

	/**
	 * UDPServerListener constructor.
	 * @param Server|Port $server
	 * @param array|null $settings
	 * @return Server\Port
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function bindCallback(Server|Port $server, ?array $settings = []): Server\Port
	{
		$this->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);

		$server->set(array_merge($settings['settings'] ?? [], ['enable_unsafe_event' => false]));
		$server->on('request', [$this, 'onRequest']);
		$server->on('connect', [$this, 'onConnect']);
		$server->on('close', [$this, 'onClose']);
		return $server;
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onConnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::CONNECT, null, [$server, $fd]);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onRequest(Request $request, Response $response)
	{
		try {
			Context::setContext(Response::class, $response);

			$this->router->dispatch(HSRequest::create($request));
		} catch (Error | Throwable $exception) {
            $this->response->send(jTraceEx($exception),500);
		}
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onDisconnect(Server $server, int $fd)
	{
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd)
	{
	}

}
