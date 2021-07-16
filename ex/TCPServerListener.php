<?php

require_once 'ListenerHelper.php';

use Swoole\Server;


/**
 * Class TCPServerListener
 * @package HttpServer\Service
 */
class TCPServerListener
{

	use ListenerHelper;

	protected static mixed $_tcp;


	/**
	 * UDPServerListener constructor.
	 * @param Server $server
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array|null $settings
	 */
	public static function instance(Server $server, string $host, int $port, int $mode, ?array $settings = [])
	{
		if (!in_array($mode, [SWOOLE_TCP, SWOOLE_TCP6])) {
			trigger_error('Port mode ' . $host . '::' . $port . ' must is tcp listener type.');
		}
		static::$_tcp = $server->addlistener($host, $port, $mode);
		static::$_tcp->set($settings['settings'] ?? []);
		static::$_tcp->on('receive', $settings['events'][BASEServerListener::SERVER_ON_RECEIVE] ?? [static::class, 'onReceive']);
		static::onConnectAndClose($server, static::$_tcp);
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
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 */
	public static function onReceive(Server $server, int $fd, int $reactor_id, string $data)
	{
		var_dump($data);
		$server->send($fd, $data);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public static function onClose(Server $server, int $fd)
	{
	}

}
