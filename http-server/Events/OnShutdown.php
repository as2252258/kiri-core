<?php

namespace Server\Events;

use Swoole\Server;

class OnShutdown
{


	/**
	 * @param Server|null $server
	 */
	public function __construct(?Server $server = null)
	{
	}

}