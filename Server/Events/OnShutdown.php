<?php

namespace Server\Events;

use Swoole\Server;

class OnShutdown
{


	/**
	 * @param Server $server
	 */
	public function __construct(Server $server)
	{
	}

}
