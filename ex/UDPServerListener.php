<?php


use Swoole\Server;


/**
 * Class UDPServerListener
 * @package HttpServer\Service
 */
class UDPServerListener
{

	protected mixed $_udp;


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
		$this->_udp = $server->addlistener($host, $port, $mode);
		$this->_udp->set($settings['settings'] ?? []);
		$this->_udp->on('packet', $settings['events'][BASEServerListener::SERVER_ON_PACKET] ?? [$this, 'onPacket']);
	}


	/**
	 * @param Server $server
	 * @param string $data
	 * @param array $clientInfo
	 */
	public function onPacket(Server $server, string $data, array $clientInfo)
	{
		$server->sendto($clientInfo['address'], $clientInfo['port'], $data);
	}

}
