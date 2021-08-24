<?php

namespace Server\SInterface;

use Swoole\Server;


/**
 *
 */
interface OnReceive
{


	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactor_id
	 * @param string $data
	 * @return void
	 */
	public function onReceive(Server $server, int $fd, int $reactor_id, string $data): void;


}
