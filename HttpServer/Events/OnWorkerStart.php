<?php
declare(strict_types=1);

namespace HttpServer\Events;

use Annotation\Target;
use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Exception\ConfigException;
use Snowflake\Snowflake;
use Swoole\Coroutine;
use Swoole\Server;
use Swoole\Timer;

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

		$this->set_process_name($server, $worker_id);
		if ($worker_id >= $server->setting['worker_num']) {
			$this->onTask($server, $worker_id);
		} else {
			$this->onWorker($server, $worker_id);
		}
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @throws Exception
	 */
	public function onTask(Server $server, int $worker_id)
	{
		putenv('environmental=' . Snowflake::TASK);

        Snowflake::setTaskId($server->worker_pid);

        fire(Event::SERVER_TASK_START);
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @throws Exception
	 */
	public function onWorker(Server $server, int $worker_id)
	{
		Snowflake::setWorkerId($server->worker_pid);
		putenv('environmental=' . Snowflake::WORKER);

		try {
			fire(Event::SERVER_WORKER_START, [$worker_id]);
		} catch (\Throwable $exception) {
			$this->addError($exception);
			write($exception->getMessage(), 'worker');
		}
	}


	/**
	 * @param $socket
	 * @param $worker_id
	 * @throws Exception
	 */
	private function set_process_name($socket, $worker_id): void
	{
		if ($worker_id >= $socket->setting['worker_num']) {
			name('Task: No.' . $worker_id);
		} else {
			name('Worker: No.' . $worker_id);
		}
	}


}
