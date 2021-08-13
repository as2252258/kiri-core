<?php

namespace Server\SInterface;

use Swoole\Server;
use Swoole\WebSocket\Frame;

interface OnMessage
{


	/**
	 * @param Server $server
	 * @param Frame $frame
	 * @return void
	 */
	public function onMessage(Server $server, Frame $frame): void;

}