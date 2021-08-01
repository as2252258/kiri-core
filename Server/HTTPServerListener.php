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
		$this->response = Snowflake::getApp('response');
		parent::__construct();
	}

	/**
	 * UDPServerListener constructor.
	 * @param Server|\Swoole\WebSocket\Server|\Swoole\Http\Server $server
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array|null $settings
	 * @return Server\Port
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public static function instance(mixed $server, string $host, int $port, int $mode, ?array $settings = []): Server\Port
	{
		if (!in_array($mode, [SWOOLE_TCP, SWOOLE_TCP6])) {
			trigger_error('Port mode ' . $host . '::' . $port . ' must is udp listener type.');
		}
		/** @var static $reflect */
		$reflect = Snowflake::getDi()->getReflect(static::class)?->newInstance();
		$reflect->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);
		static::$_http = $server->addlistener($host, $port, $mode);
		if (!(static::$_http instanceof Port)) {
			trigger_error('Port is  ' . $host . '::' . $port . ' must is tcp listener type.');
		}
		static::$_http->set(array_merge($settings['settings'] ?? [], ['enable_unsafe_event' => false]));
		static::$_http->on('request', [$reflect, 'onRequest']);
		static::$_http->on('connect', [$reflect, 'onConnect']);
		static::$_http->on('disconnect', [$reflect, 'onDisconnect']);
		static::$_http->on('close', [$reflect, 'onClose']);
		return static::$_http;
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

			$result = $this->router->dispatch(HSRequest::create($request));
		} catch (Error | Throwable $exception) {
			$result = $this->router->exception($exception);
		} finally {
			$this->response->send($result);
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
