<?php

namespace Server;

use Exception;
use HttpServer\Route\Router;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;


/**
 * Class HTTPServerListener
 * @package Server
 */
class HTTPServerListener extends Abstracts\Server
{

	protected static mixed $_http;

	use ListenerHelper;

	private Router $router;


	/**
	 * HTTPServerListener constructor.
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->router = Snowflake::getApp('router');
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
		static::$_http = $server->addlistener($host, $port, $mode);
		static::$_http->set($settings['settings'] ?? []);
		static::$_http->on('request', [$reflect, 'onRequest']);
		static::$_http->on('connect', [$reflect, 'onConnect']);
		static::$_http->on('disconnect', [$reflect, 'onDisconnect']);

		$reflect->setEvents(Constant::DISCONNECT, $settings['events'][Constant::DISCONNECT] ?? null);
		$reflect->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);

		return static::$_http;
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onConnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::CONNECT, null, [$server, $fd]);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function onRequest(Request $request, Response $response)
	{
		$this->router->find_path(new \HttpServer\Http\Request());

		if (!$response->isWritable()) {
			return;
		}
		$response->status(200);
		$response->end('');
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onDisconnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::CONNECT, null, [$server, $fd]);
	}

}
