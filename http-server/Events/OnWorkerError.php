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
	 * @param int $worker_id
	 * @param int $worker_pid
	 * @param int $exit_code
	 * @param int $signal
	 */
	public function __construct(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{
	}


}
