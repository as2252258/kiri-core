<?php

namespace Server;

use Exception;
use ReflectionException;
use Snowflake\Exception\NotFindClassException;
use Snowflake\Snowflake;
use Swoole\Server;


/**
 * Class TCPServerListener
 * @package HttpServer\Service
 */
class TCPServerListener extends Abstracts\Server
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
	 * @return Server\Port
	 * @throws NotFindClassException
	 * @throws ReflectionException
	 */
	public static function instance(Server $server, string $host, int $port, int $mode, ?array $settings = []): Server\Port
	{
		if (!in_array($mode, [SWOOLE_TCP, SWOOLE_TCP6])) {
			trigger_error('Port mode ' . $host . '::' . $port . ' must is tcp listener type.');
		}

		/** @var static $reflect */
		$reflect = Snowflake::getDi()->getReflect(static::class)?->newInstance();
		static::$_tcp = $server->addlistener($host, $port, $mode);
		static::$_tcp->set($settings['settings'] ?? []);
		static::$_tcp->on('receive', [$reflect, 'onReceive']);
		static::$_tcp->on('connect', [$reflect, 'onConnect']);
		if (swoole_version() >= '4.7.0') {
			static::$_tcp->on('disconnect', [$reflect, 'onDisconnect']);
			$reflect->setEvents(Constant::DISCONNECT, $settings['events'][Constant::DISCONNECT] ?? null);
		} else {
			static::$_tcp->on('close', [$reflect, 'onClose']);
			$reflect->setEvents(Constant::CLOSE, $settings['events'][Constant::CLOSE] ?? null);
		}
		$reflect->setEvents(Constant::RECEIVE, $settings['events'][Constant::RECEIVE] ?? null);
		$reflect->setEvents(Constant::CONNECT, $settings['events'][Constant::CONNECT] ?? null);

		return static::$_tcp;
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int|null $reactor_id
	 */
	public function onDisconnect(Server $server, int $fd, ?int $reactor_id = null)
	{
		$this->runEvent(Constant::HANDSHAKE, null, [$server, $fd, $reactor_id]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onConnect(Server $server, int $fd)
	{
		$this->runEvent(Constant::CONNECT, null, [$server, $fd]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 */
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data)
	{
		$this->runEvent(Constant::RECEIVE, null, [$server, $fd, $reactor_id, $data]);
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd)
	{
		$this->runEvent(Constant::CLOSE, null, [$server, $fd]);
	}

}
