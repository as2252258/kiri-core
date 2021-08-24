<?php

namespace Server\SInterface;

use Swoole\Server;


/**
 *
 */
interface OnClose
{


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onClose(Server $server, int $fd): void;


	/**
	 * @param Server $server
	 * @param int $fd
	 */
	public function onDisconnect(Server $server, int $fd): void;


}
