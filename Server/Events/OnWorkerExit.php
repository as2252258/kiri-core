<?php

namespace Server\Events;

use Swoole\Server;

/**
 *
 */
class OnWorkerExit
{


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public function __construct(Server $server, int $workerId)
	{
	}


}