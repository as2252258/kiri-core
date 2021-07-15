<?php

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;
use Swoole\WebSocket\Frame;


/**
 * Class WebSocketServerListener
 * @package HttpServer\Service
 */
class WebSocketServerListener
{

	protected mixed $_http;


	/**
	 * UDPServerListener constructor.
	 * @param Server|\Swoole\WebSocket\Server|\Swoole\Http\Server $server
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array|null $settings
	 */
	public function __construct(mixed $server, string $host, int $port, int $mode, ?array $settings = [])
	{
		$this->_http = $server->addlistener($host, $port, $mode);
		$this->_http->set($settings['settings'] ?? []);
		$this->_http->on('handshake', $settings['events'][BASEServerListener::SERVER_ON_HANDSHAKE] ?? [$this, 'onHandshake']);
		$this->_http->on('message', $settings['events'][BASEServerListener::SERVER_ON_MESSAGE] ?? [$this, 'onMessage']);
		$this->_http->on('connect', $settings['events'][BASEServerListener::SERVER_ON_CONNECT] ?? [$this, 'onConnect']);
		$this->_http->on('close', $settings['events'][BASEServerListener::SERVER_ON_CLOSE] ?? [$this, 'onClose']);
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public function onHandshake(Request $request, Response $response)
	{

	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onConnect(Server $server, int $fd)
	{
		var_dump(__FILE__ . ':' . __LINE__);
	}


	/**
	 * @param \Swoole\WebSocket\Server|Server $server
	 * @param Frame $frame
	 */
	public function onMessage(\Swoole\WebSocket\Server|Server $server, Frame $frame)
	{
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd)
	{
	}

}
