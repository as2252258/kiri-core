<?php
/**
 * Created by PhpStorm.
 * User: whwyy
 * Date: 2018/11/8 0008
 * Time: 18:15
 */

namespace HttpServer\Events;

use Exception;
use HttpServer\ServerManager;
use ReflectionException;
use Snowflake\Application;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Http\Request as SRequest;
use Swoole\Http\Response as SResponse;
use Swoole\Process\Pool;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use HttpServer\Route\Annotation\Websocket as AWebsocket;

/**
 * Class ServerWebSocket
 * @package Snowflake\Snowflake\Server
 */
class WebSocket extends Server
{
	public $namespace = 'App\\Sockets\\';

	public $callback = [];


	/** @var Application */
	public $application;


	/**
	 * WebSocket constructor.
	 * @param $application
	 * @param $host
	 * @param null $port
	 * @param null $mode
	 * @param null $sock_type
	 */
	public function __construct($application, $host, $port = null, $mode = null, $sock_type = null)
	{
		parent::__construct($host, $port, $mode, $sock_type);
		$this->application = $application;
	}


	/**
	 * @param array $settings
	 * @param null $pool
	 * @param array $events
	 * @param array $config
	 * @return mixed|void
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function set(array $settings, $pool = null, $events = [], $config = [])
	{
		parent::set($settings);

		$application = Snowflake::get();
		$application->set(WebSocket::class, $this);
		$application->set(Pool::class, $pool);

		ServerManager::set($this, $settings, $application, $events, $config);
	}


	/**
	 * @param Server $server
	 * @param Frame $frame
	 * @throws
	 */
	public function onMessage(Server $server, Frame $frame)
	{
		try {
			if ($frame->opcode == 0x08) {
				return;
			}

			$event = Snowflake::get()->event;
			if ($event->exists(Event::SERVER_MESSAGE)) {
				$event->trigger(Event::SERVER_MESSAGE, [$server, $frame]);
			} else {
				$frame->data = json_decode($frame->data, true);
			}

			/** @var AWebsocket $manager */
			$manager = Snowflake::get()->annotation->get('websocket');
			$manager->runWith($manager->getName(AWebsocket::MESSAGE, [null, null, $frame->data['route']]), [$frame, $server]);
		} catch (Exception $exception) {
			$this->application->addError($exception->getMessage(), 'websocket');
			$server->send($frame->fd, $exception->getMessage());
		} finally {
			$event = Snowflake::get()->event;
			$event->trigger(Event::EVENT_AFTER_REQUEST);
			Logger::insert();
		}
	}

	/**
	 * @param SRequest $request
	 * @param SResponse $response
	 * @return bool
	 * @throws Exception
	 */
	protected function connect($request, $response)
	{
		$manager = Snowflake::get()->event;
		if ($manager->exists(Event::SERVER_HANDSHAKE)) {
			return $manager->trigger(Event::SERVER_HANDSHAKE, [$request, $response]);
		}
		$response->status(502);
		$response->end();
		return true;
	}

	/**
	 * @param SRequest $request
	 * @param SResponse $response
	 * @return bool|string
	 * @throws Exception
	 */
	public function onHandshake(SRequest $request, SResponse $response)
	{
		/** @var Server $server */
		$secWebSocketKey = $request->header['sec-websocket-key'];
		$patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
		if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
			return false;
		}
		$key = base64_encode(sha1(
			$request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
			TRUE
		));
		$headers = [
			'Upgrade'               => 'websocket',
			'Connection'            => 'Upgrade',
			'Sec-websocket-Accept'  => $key,
			'Sec-websocket-Version' => '13',
		];
		if (isset($request->header['sec-websocket-protocol'])) {
			$headers['Sec-websocket-Protocol'] = $request->header['sec-websocket-protocol'];
		}
		foreach ($headers as $key => $val) {
			$response->header($key, $val);
		}
		if (isset($request->get['debug']) && $request->get['debug'] == 'test') {
			$response->status(101);
			$response->end();
			return true;
		} else {
			return $this->connect($request, $response);
		}
	}

	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd)
	{
		$event = Snowflake::get()->event;
		try {
			if ($event->exists(Event::SERVER_CLOSE)) {
				$event->trigger(Event::SERVER_CLOSE, [$fd]);
			}
		} catch (\Throwable $exception) {
			$this->application->addError($exception->getMessage());
		} finally {
			$event->trigger(Event::RELEASE_ALL);
			Logger::insert();
		}
	}

}
