<?php

namespace Server;

use Exception;
use ReflectionException;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\Server\Port;
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
	 * @param array|null $settings
	 * @return Port|Server
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public function bindCallback(Server|Port $server, ?array $settings = []): Server|Port
	{
		$this->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);
		$this->setEvents(Constant::HANDSHAKE, $settings['events'][Constant::HANDSHAKE] ?? null);
		$this->setEvents(Constant::MESSAGE, $settings['events'][Constant::MESSAGE] ?? null);
		$this->setEvents(Constant::CLOSE, $settings['events'][Constant::CLOSE] ?? null);

		$server->set($settings['settings'] ?? []);
		$server->on('connect', [$this, 'onConnect']);
		$server->on('handshake', [$this, 'onHandshake']);
		$server->on('message', [$this, 'onMessage']);
		$server->on('close', [$this, 'onClose']);

		if (swoole_version() >= '4.7' && isset($settings['events'][Constant::DISCONNECT])) {
			$this->setEvents(Constant::DISCONNECT, $settings['events'][Constant::DISCONNECT]);
			$server->on('disconnect', [$this, 'onDisconnect']);
		}

		$events = $settings['events'][Constant::REQUEST] ?? [];
		if (!empty($events) && is_array($events)) {
			$events[0] = Snowflake::getDi()->get($events[0]);
			$server->on('request', $events);
		}
		return $server;
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

		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
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
	 * @throws Exception
	 */
	public function onConnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::CONNECT, fn() => $server->confirm($fd), [$server, $fd]);

		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
	}


	/**
	 * @param \Swoole\WebSocket\Server $server
	 * @param Frame $frame
	 * @throws Exception
	 */
	public function onMessage(\Swoole\WebSocket\Server $server, Frame $frame)
	{
		$this->runEvent(Constant::MESSAGE, fn() => $server->push($frame->fd, '.'), [$server, $frame]);

		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd)
	{
		$this->runEvent(Constant::CLOSE, fn() => $server->confirm($fd), [$server, $fd]);

		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onDisconnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::DISCONNECT, fn() => $server->confirm($fd), [$server, $fd]);

		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
	}

}
