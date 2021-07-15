<?php


use Swoole\Server;


/**
 * Class TCPServerListener
 * @package HttpServer\Service
 */
class TCPServerListener
{

	protected mixed $_tcp;


	/**
	 * UDPServerListener constructor.
	 * @param Server $server
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array|null $settings
	 */
	public function __construct(Server $server, string $host, int $port, int $mode, ?array $settings = [])
	{
		$this->_tcp = $server->addlistener($host, $port, $mode);
		$this->_tcp->set($settings);
		$this->_tcp->on('receive', $settings['events'][BASEServerListener::SERVER_ON_RECEIVE] ?? [$this, 'onReceive']);
		if (!in_array($server->setting['dispatch_mode'] ?? 2, [1, 3]) || $server->setting['enable_unsafe_event'] ?? false == true) {
			$this->_tcp->on('connect', $settings['events'][BASEServerListener::SERVER_ON_CONNECT] ?? [$this, 'onConnect']);
			$this->_tcp->on('close', $settings['events'][BASEServerListener::SERVER_ON_CLOSE] ?? [$this, 'onClose']);
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
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 */
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data)
	{
		$server->send($fd, $data);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd)
	{
	}

}
