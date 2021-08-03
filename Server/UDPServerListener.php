<?php

namespace Server;

use Annotation\Inject;
use Exception;
use ReflectionException;
use Server\Events\OnAfterRequest;
use Snowflake\Event;
use Snowflake\Events\EventDispatch;
use Snowflake\Exception\NotFindClassException;
use Swoole\Server;
use Swoole\Server\Port;


/**
 * Class UDPServerListener
 * @package HttpServer\Service
 */
class UDPServerListener extends Abstracts\Server
{


	/** @var EventDispatch  */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


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

		$this->eventDispatch->dispatch(new OnAfterRequest());
	}

}
