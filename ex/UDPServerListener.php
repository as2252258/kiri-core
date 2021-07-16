<?php

require_once 'ListenerHelper.php';

use Swoole\Server;


/**
 * Class UDPServerListener
 * @package HttpServer\Service
 */
class UDPServerListener
{

	protected static mixed $_udp;


	/**
	 * @param Server $server
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array|null $settings
	 */
	public static function instance(Server $server, string $host, int $port, int $mode, ?array $settings = [])
	{
		if (!in_array($mode, [SWOOLE_UDP, SWOOLE_UDP6])) {
			trigger_error('Port mode ' . $host . '::' . $port . ' must is udp listener type.');
		}
		static::$_udp = $server->addlistener($host, $port, $mode);
		static::$_udp->set($settings['settings'] ?? []);
		static::$_udp->on('packet', $settings['events'][BASEServerListener::SERVER_ON_PACKET] ?? [static::class, 'onPacket']);
	}


	/**
	 * @param Server $server
	 * @param string $data
	 * @param array $clientInfo
	 */
	public static function onPacket(Server $server, string $data, array $clientInfo)
	{
		$server->sendto($clientInfo['address'], $clientInfo['port'], $data);
	}

}
