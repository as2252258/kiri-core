<?php


use Swoole\Server;

class ServerWorker
{


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public static function onWorkerStart(Server $server, int $workerId)
	{

	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public static function onWorkerStop(Server $server, int $workerId)
	{

	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public static function onWorkerExit(Server $server, int $workerId)
	{

	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @param int $worker_pid
	 * @param int $exit_code
	 * @param int $signal
	 */
	public static function onWorkerError(Server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal)
	{

	}

}
