<?php

namespace Server\Service;

use Server\SInterface\OnClose;
use Server\SInterface\OnConnect;
use Swoole\Server;


/**
 *
 */
class Tcp extends \Server\Abstracts\Tcp implements OnConnect, OnClose
{


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onConnect(Server $server, int $fd): void
	{
		// TODO: Implement onConnect() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 */
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data): void
	{
		// TODO: Implement onReceive() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd): void
	{
		// TODO: Implement onClose() method.
	}


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onDisconnect(Server $server, int $fd): void
	{
		// TODO: Implement onDisconnect() method.
	}
}
