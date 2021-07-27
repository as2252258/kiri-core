<?php

namespace Server;

use Exception;
use HttpServer\Route\Node;
use HttpServer\Route\Router;
use ReflectionException;
use Snowflake\Event;
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


	/**
	 * HTTPServerListener constructor.
	 * @throws Exception
	 */
	public function __construct()
	{
		$this->router = Snowflake::getApp('router');
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
		static::$_http = $server->addlistener($host, $port, $mode);
		if (!(static::$_http instanceof Port)) {
			trigger_error('Port is  ' . $host . '::' . $port . ' must is tcp listener type.');
		}

		static::$_http->set($settings['settings'] ?? []);
		static::$_http->on('request', [$reflect, 'onRequest']);
		static::$_http->on('connect', [$reflect, 'onConnect']);
		static::$_http->on('close', [$reflect, 'onClose']);
		if (swoole_version() >= '4.7.0') {
			static::$_http->on('disconnect', [$reflect, 'onDisconnect']);
			$reflect->setEvents(Constant::DISCONNECT, $settings['events'][Constant::DISCONNECT] ?? null);
		}
		$reflect->setEvents(Constant::CLOSE, $settings['events'][Constant::CLOSE] ?? null);
		$reflect->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);
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

		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onRequest(Request $request, Response $response)
	{
		try {
			defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));
			[$sRequest, $sResponse] = $this->request($request, $response);

			$result = $this->router->dispatch($sRequest);
		} catch (Error | Throwable $exception) {
			$result = $this->router->exception($exception);
		} finally {
			if (!isset($sResponse)){
				return;
			}
			$sResponse->send($result);
		}
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @return array
	 * @throws Exception
	 */
	public function request(Request $request, Response $response): array
	{
		return [\HttpServer\Http\Request::create($request), \HttpServer\Http\Response::create($response)];
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onDisconnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::DISCONNECT, null, [$server, $fd]);

//		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd)
	{
		$this->runEvent(Constant::CLOSE, null, [$server, $fd]);

//		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
	}

}
