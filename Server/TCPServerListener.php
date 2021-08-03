<?php

namespace Server;

use Annotation\Inject;
use Exception;
use Server\Events\OnAfterRequest;
use Snowflake\Event;
use Snowflake\Events\EventDispatch;
use Swoole\Server;
use Swoole\Server\Port;


/**
 * Class TCPServerListener
 * @package HttpServer\Service
 */
class TCPServerListener extends Abstracts\Server
{

	use ListenerHelper;

	protected static bool|Port $_tcp;


	/** @var EventDispatch */
	#[Inject(EventDispatch::class)]
	public EventDispatch $eventDispatch;


	/**
	 * UDPServerListener constructor.
	 * @param Server|Port $server
	 * @param array|null $settings
	 * @return Server|Port
	 * @throws Exception
	 */
	public function bindCallback(Server|Port $server, ?array $settings = []): Server|Port
	{
		$this->setEvents(Constant::CLOSE, $settings['events'][Constant::CLOSE] ?? null);
		$this->setEvents(Constant::RECEIVE, $settings['events'][Constant::RECEIVE] ?? null);
		$this->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);

		$server->set($settings['settings'] ?? []);
		$server->on('receive', [$this, 'onReceive']);
		$server->on('connect', [$this, 'onConnect']);
		$server->on('close', [$this, 'onClose']);
		return $server;
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onConnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::CONNECT, null, [$server, $fd]);

		$this->eventDispatch->dispatch(new OnAfterRequest());
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 * @throws Exception
	 */
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data)
	{
		$this->runEvent(Constant::RECEIVE, null, [$server, $fd, $reactor_id, $data]);

		$this->eventDispatch->dispatch(new OnAfterRequest());
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @throws Exception
	 */
	public function onClose(Server $server, int $fd)
	{
		$this->runEvent(Constant::CLOSE, null, [$server, $fd]);


		$this->eventDispatch->dispatch(new OnAfterRequest());
	}

}
