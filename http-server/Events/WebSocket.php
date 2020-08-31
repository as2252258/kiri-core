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
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Http\Request as SRequest;
use Swoole\Http\Response as SResponse;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

/**
 * Class ServerWebSocket
 * @package Snowflake\Snowflake\Server
 */
class WebSocket extends Server
{
	public $namespace = 'App\\Sockets\\';

	public $callback = [];


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
		$this->application = $application;
		parent::__construct($host, $port, $mode, $sock_type);
	}


	/**
	 * @param array $settings
	 * @param array $events
	 * @param $config
	 * @return mixed|void
	 * @throws \ReflectionException
	 * @throws NotFindClassException
	 */
	public function set(array $settings, $events = [], $config = [])
	{
		parent::set($settings);
		ServerManager::set($this, $settings, $this->application, $events, $config);
	}


	/**
	 * @param Server $server
	 * @param Frame $frame
	 * @throws
	 */
	public function onMessage(Server $server, Frame $frame)
	{
		try {
			$event = Snowflake::get()->event;
			if ($event->exists(Event::SERVER_MESSAGE)) {
				$event->trigger(Event::SERVER_MESSAGE, [$server, $frame]);
				return;
			}
			if ($frame->opcode == 0x08) {
				return;
			}
			$json = json_decode($frame->data, true);

			$manager = Snowflake::get()->annotation;
			$manager->runWith($this->getName($json), [$frame->fd, $server]);
		} catch (Exception $exception) {
//			$this->error($exception->getMessage(), __METHOD__, __FILE__);
//			$this->addError($exception->getMessage());
		} finally {
			$event = Snowflake::get()->event;
			$event->trigger(Event::EVENT_AFTER_REQUEST);
			Logger::insert();
		}
	}

	/**
	 * @param $json
	 * @return string
	 */
	private function getName($json)
	{
		return 'WEBSOCKET:MESSAGE:' . $json['route'];
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
//			$this->addError($exception->getMessage());
		} finally {
			$event->trigger(Event::RELEASE_ALL);
			Logger::insert();
		}
	}

}
