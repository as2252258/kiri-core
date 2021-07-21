<?php

namespace Server;

use Exception;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\WebSocket\Frame;


/**
 * Class WebSocketServerListener
 * @package HttpServer\Service
 */
class WebSocketServerListener extends Abstracts\Server
{

	protected static Server\Port $_http;

	use ListenerHelper;

	/**
	 * @param mixed $server
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
		static::$_http->on('connect', [$reflect, 'onConnect']);
		static::$_http->on('handshake', [$reflect, 'onHandshake']);
		static::$_http->on('message', [$reflect, 'onMessage']);
		static::$_http->on('close', [$reflect, 'onClose']);

		$reflect->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);
		$reflect->setEvents(Constant::HANDSHAKE, $settings['events'][Constant::HANDSHAKE] ?? null);
		$reflect->setEvents(Constant::MESSAGE, $settings['events'][Constant::MESSAGE] ?? null);
		$reflect->setEvents(Constant::CLOSE, $settings['events'][Constant::CLOSE] ?? null);

		return static::$_http;
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 * @throws Exception
	 */
	public function onHandshake(Request $request, Response $response)
	{
		/** @var \Swoole\WebSocket\Server $server */
		$secWebSocketKey = $request->header['sec-websocket-key'];
		$patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
		if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
			throw new Exception('protocol error.', 500);
		}
		$key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE));
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
			$response->setHeader($key, $val);
		}
		$this->runEvent(Constant::HANDSHAKE, fn() => $this->disconnect($request, $response), [$request, $response]);
	}


	/**
	 * @param $request
	 * @param $response
	 */
	public function disconnect($request, $response)
	{
		$response->setStatusCode(502);
		$response->end();
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onConnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::CONNECT, fn() => $server->confirm($fd), [$server, $fd]);
	}


	/**
	 * @param \Swoole\WebSocket\Server $server
	 * @param Frame $frame
	 */
	public function onMessage(\Swoole\WebSocket\Server $server, Frame $frame)
	{
		$this->runEvent(Constant::MESSAGE, fn() => $server->push($frame->fd, '.'), [$server, $frame]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd)
	{
		$this->runEvent(Constant::CLOSE, fn() => $server->confirm($fd), [$server, $fd]);
	}

}
