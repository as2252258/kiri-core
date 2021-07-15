<?php


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
		static::$_http = $server->addlistener($host, $port, $mode);
		static::$_http->set($settings['settings'] ?? []);
		if ($server->getCallback('request') === null) {
			$server->on('request', $settings['events'][BASEServerListener::SERVER_ON_REQUEST] ?? [static::class, 'onRequest']);
		}
        static::onConnectAndClose($server, static::$_http);
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
	 * @param Request $request
	 * @param Response $response
	 */
	public static function onRequest(Request $request, Response $response)
	{
		$response->setStatusCode(200);
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
