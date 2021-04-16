<?php
declare(strict_types=1);

namespace HttpServer\Events;

use Annotation\Annotation;
use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine\System;
use Swoole\Server;

/**
 * Class OnWorkerStart
 * @package HttpServer\Events
 */
class OnWorkerStart extends Callback
{


	/**
	 * @param Server $server
	 * @param int $worker_id
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $worker_id): void
	{
		putenv('state=start');
		putenv('worker=' . $worker_id);

		$content = System::readFile(storage('runtime.php'));

		$annotation = Snowflake::app()->getAnnotation();
		$annotation->setLoader(unserialize($content));
		$annotation->runtime(directory('app'));

		if ($worker_id < $server->setting['worker_num']) {
			$this->onWorker($server);
		} else {
			$this->onTask($server);
		}
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @return bool
	 */
	private function isWorker(Server $server, int $worker_id): bool
	{
		return $worker_id < $server->setting['worker_num'];
	}


	/**
	 * @param Server $server
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function onTask(Server $server)
	{
		putenv('environmental=' . Snowflake::TASK);

		name($server->worker_pid, 'Task#' . $server->worker_id);

		Snowflake::setTaskId($server->worker_pid);

		fire(Event::SERVER_TASK_START);
	}


	/**
	 * @param Server $server
	 * @throws Exception
	 */
	public function onWorker(Server $server)
	{
		try {
			name($server->worker_pid, 'Worker#' . $server->worker_id);

			Snowflake::setWorkerId($server->worker_pid);
			putenv('environmental=' . Snowflake::WORKER);

			fire(Event::SERVER_WORKER_START, [getenv('worker')]);
		} catch (\Throwable $exception) {
			$this->addError($exception, 'throwable');
			write($exception->getMessage(), 'worker');
		}
	}

}
