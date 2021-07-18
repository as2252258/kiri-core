<?php

namespace Server\Worker;

use Swoole\Server;

class ServerWorker
{


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public function onWorkerStart(Server $server, int $workerId)
	{

	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public function onWorkerStop(Server $server, int $workerId)
	{

	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public function onWorkerExit(Server $server, int $workerId)
	{

	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @param int $worker_pid
	 * @param int $exit_code
	 * @param int $signal
	 */
	public function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{

	}

}
