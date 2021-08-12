<?php

namespace Server\SInterface;

use Swoole\Server;

interface OnConnect
{


	/**
	 * @param Server $server
	 * @param int $fd
	 * @return void
	 */
	public function onConnect(Server $server, int $fd): void;

}
