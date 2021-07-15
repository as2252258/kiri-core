<?php


namespace Task;


use SInterface\TaskExecute;
use Swoole\Server;

class ServerTask
{


	/**
	 * @param Server $server
	 * @param int $task_id
	 * @param int $src_worker_id
	 * @param mixed $data
	 */
	public static function onTask(Server $server, int $task_id, int $src_worker_id, mixed $data)
	{
		try {
			$data = unserialize($data);
			if (!($data instanceof TaskExecute)) {
				return;
			}
			$data->execute();
		} catch (\Throwable $exception) {
			$data = [$exception->getMessage()];
		} finally {
			$server->finish(serialize($data));
		}
	}


	/**
	 * @param Server $server
	 * @param Server\Task $task
	 */
	public static function onCoroutineTask(Server $server, Server\Task $task)
	{
		try {
			$data = unserialize($task->data);
			if (!($data instanceof TaskExecute)) {
				return;
			}
			$data->execute();
		} catch (\Throwable $exception) {
			$data = [$exception->getMessage()];
		} finally {
			$server->finish(serialize($data));
		}
	}


	/**
	 * @param Server $server
	 * @param int $task_id
	 * @param mixed $data
	 */
	public static function onFinish(Server $server, int $task_id, mixed $data)
	{
		if (!($data instanceof TaskExecute)) {
			return;
		}
		$data->finish($server, $task_id);
	}


}
