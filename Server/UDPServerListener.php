<?php

namespace Server;

use Exception;
use ReflectionException;
use Snowflake\Event;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Server;


/**
 * Class UDPServerListener
 * @package HttpServer\Service
 */
class UDPServerListener extends Abstracts\Server
{

	protected static mixed $_udp;


	use ListenerHelper;


	/**
	 * @param Server $server
	 * @param string $host
	 * @param int $port
	 * @param int $mode
	 * @param array|null $settings
	 * @return Server\Port
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 * @throws Exception
	 */
	public static function instance(Server $server, string $host, int $port, int $mode, ?array $settings = []): Server\Port
	{
		if (!in_array($mode, [SWOOLE_UDP, SWOOLE_UDP6])) {
			trigger_error('Port mode ' . $host . '::' . $port . ' must is udp listener type.');
		}

		/** @var static $reflect */
		$reflect = Snowflake::getDi()->getReflect(static::class)->newInstance();

		static::$_udp = $server->addlistener($host, $port, $mode);
		static::$_udp->set($settings['settings'] ?? []);
		static::$_udp->on('packet', [$reflect, 'onPacket']);

		$reflect->setEvents(Constant::PACKET, $settings['events'][Constant::PACKET] ?? null);

		return static::$_udp;
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
