<?php

namespace Server;

use Exception;
use ReflectionException;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
use Swoole\Server;
use Swoole\Server\Port;


/**
 * Class UDPServerListener
 * @package HttpServer\Service
 */
class UDPServerListener extends Abstracts\Server
{

	protected static bool|Port $_udp;


	use ListenerHelper;


	/**
	 * @param Server|Port $server
	 * @param array|null $settings
	 * @return Server|Port
	 * @throws Exception
	 */
	public function bindCallback(Server|Port $server, ?array $settings = []): Server|Port
	{
		$this->setEvents(Constant::PACKET, $settings['events'][Constant::PACKET] ?? null);

		$server->set($settings['settings'] ?? []);
		$server->on('packet', [$this, 'onPacket']);

		return $server;
	}


	/**
	 * @param Server $server
	 * @param string $data
	 * @param array $clientInfo
	 * @throws Exception
	 */
	public function onPacket(Server $server, string $data, array $clientInfo)
	{
		$this->runEvent(Constant::MESSAGE, null, [$server, $data, $clientInfo]);

		$this->_event->dispatch(Event::SYSTEM_RESOURCE_RELEASES);
	}

}
