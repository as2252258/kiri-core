<?php

require_once 'ListenerHelper.php';
require_once 'Router.php';

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

class HTTPServerListener
{

	protected static mixed $_http;

	use ListenerHelper;


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
		if (!in_array($mode, [SWOOLE_TCP, SWOOLE_TCP6])) {
			trigger_error('Port mode ' . $host . '::' . $port . ' must is udp listener type.');
		}
		static::$_http = $server->addlistener($host, $port, $mode);
		static::$_http->set($settings['settings'] ?? []);
		static::$_http->on('request', $settings['events'][BASEServerListener::SERVER_ON_REQUEST] ?? [static::class, 'onRequest']);
		static::onConnectAndClose($server, static::$_http);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public static function onConnect(Server $server, int $fd)
	{
	}


	/**
	 * @param Request $request
	 * @param Response $response
	 */
	public static function onRequest(Request $request, Response $response)
	{
		$controller = Router::findPath($request->server['request_uri']);
		if (empty($controller)) {
			$response->status(404);
		} else {
			$response->status(200);
		}
		if (!$response->isWritable()) {
			return;
		}
		$response->end('');
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public static function onClose(Server $server, int $fd)
	{
	}

}
