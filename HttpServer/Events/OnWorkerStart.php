<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Server;

/**
 * Class OnWorkerStart
 * @package HttpServer\Events
 */
class OnWorkerStart extends Callback
{


	private int $_taskTable = 0;


	/**
	 * @param Server $server
	 * @param int $worker_id
	 *
	 * @return mixed
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $worker_id): void
	{
		Coroutine::set(['enable_deadlock_check' => false]);

		$get_name = $this->get_process_name($server, $worker_id);
		if (!empty($get_name) && !Snowflake::isMac()) {
			swoole_set_process_name($get_name);
		}
		putenv('workerId=' . ($worker_id >= $server->setting['worker_num'] ? 'Task' : 'Worker') . '.' . $worker_id);
		if ($worker_id >= $server->setting['worker_num']) {
			fire(Event::SERVER_TASK_START);
		} else {
			Snowflake::setWorkerId($server->worker_pid);
			$this->setWorkerAction($worker_id);
		}
		$this->onTaskSignal($server, $worker_id);
	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 * 异步任务管制
	 * @return mixed
	 */
	public function onTaskSignal(Server $server, int $workerId): mixed
	{
		$sigkill = Coroutine::waitSignal(SIGTERM | SIGKILL | SIGUSR2 | SIGUSR1, -1);
		if ($sigkill === false) {
			return $server->stop($workerId);
		}
		while (Snowflake::app()->isRun()) {
			sleep(1);
		}
		return $server->stop($workerId);
	}


	/**
	 * @param $worker_id
	 * @throws Exception
	 */
	private function setWorkerAction($worker_id)
	{
		$event = Snowflake::app()->getEvent();
		try {
			$this->debug(sprintf('Worker #%d is start.....', $worker_id));
			$event->trigger(Event::SERVER_WORKER_START, [$worker_id]);
		} catch (\Throwable $exception) {
			$this->addError($exception);
			write($exception->getMessage(), 'worker');
		}
		try {
			$event->trigger(Event::SERVER_AFTER_WORKER_START, [$worker_id]);
		} catch (\Throwable $exception) {
			$this->addError($exception);
			write($exception->getMessage(), 'worker');
		}
	}

	/**
	 * @param $socket
	 * @param $worker_id
	 * @return string
	 * @throws ConfigException
	 */
	private function get_process_name($socket, $worker_id): string
	{
		$prefix = rtrim(Config::get('id', false, 'system:'), ':');
		if ($worker_id >= $socket->setting['worker_num']) {
			return $prefix . ': Task: No.' . $worker_id;
		} else {
			return $prefix . ': worker: No.' . $worker_id;
		}
	}


}
