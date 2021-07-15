<?php


use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

class HTTPServerListener
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
		if ($server->getCallback('request') === null) {
			$server->on('request', $settings['events'][BASEServerListener::SERVER_ON_REQUEST] ?? [$this, 'onRequest']);
		}
		if (!in_array($server->setting['dispatch_mode'] ?? 2, [1, 3]) || $server->setting['enable_unsafe_event'] ?? false == true) {
			$this->_http->on('connect', $settings['events'][BASEServerListener::SERVER_ON_CONNECT] ?? [$this, 'onConnect']);
			$this->_http->on('close', $settings['events'][BASEServerListener::SERVER_ON_CLOSE] ?? [$this, 'onClose']);
		}
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
	 * @param Request $request
	 * @param Response $response
	 */
	public function onRequest(Request $request, Response $response)
	{
		$response->setStatusCode(200);
		$response->end('');
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd)
	{
	}

}
