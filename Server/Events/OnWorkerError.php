<?php

namespace Server\Events;

use Swoole\Server;

/**
 *
 */
class OnWorkerError
{


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public function __construct(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{
	}


}
