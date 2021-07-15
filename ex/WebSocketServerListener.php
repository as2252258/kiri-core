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

	protected static mixed $_http;


	/**
	 * UDPServerListener constructor.
	 * @param Server|\Swoole\WebSocket\Server|\Swoole\Http\Server $server
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array|null $settings
	 */
	public static function instance(mixed $server, string $host, int $port, int $mode, ?array $settings = [])
	{
		static::$_http = $server->addlistener($host, $port, $mode);
		static::$_http->set($settings['settings'] ?? []);
		static::$_http->on('handshake', $settings['events'][BASEServerListener::SERVER_ON_HANDSHAKE] ?? [static::class, 'onHandshake']);
		static::$_http->on('message', $settings['events'][BASEServerListener::SERVER_ON_MESSAGE] ?? [static::class, 'onMessage']);
		static::$_http->on('connect', $settings['events'][BASEServerListener::SERVER_ON_CONNECT] ?? [static::class, 'onConnect']);
		static::$_http->on('close', $settings['events'][BASEServerListener::SERVER_ON_CLOSE] ?? [static::class, 'onClose']);
    }


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public static function onHandshake(Request $request, Response $response)
	{

	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public static function onConnect(Server $server, int $fd)
	{
		var_dump(__FILE__ . ':' . __LINE__);
	}


	/**
	 * @param \Swoole\WebSocket\Server|Server $server
	 * @param Frame $frame
	 */
	public static function onMessage(\Swoole\WebSocket\Server|Server $server, Frame $frame)
	{
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public static function onClose(Server $server, int $fd)
	{
	}

}
