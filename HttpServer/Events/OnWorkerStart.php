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
	 * @throws ConfigException
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $worker_id): void
	{
		putenv('state=start');
		putenv('worker=' . $worker_id);

		if (env('debug', 'false') == 'true') {
			$attribute = Snowflake::app()->getAttributes();
			$attribute->read(directory('app'), 'App');
		}

		if ($worker_id >= $server->setting['worker_num']) {
			$this->onTask($server, $worker_id);
		} else {
			$this->onWorker($server, $worker_id);
		}
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @throws ComponentException
	 * @throws ConfigException
	 */
	public function onTask(Server $server, int $worker_id)
	{
		putenv('environmental=' . Snowflake::TASK);

		fire(Event::SERVER_TASK_START);

		$this->set_process_name($server, $worker_id);
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @param $prefix
	 * @throws ComponentException
	 * @throws ConfigException
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
		$this->set_process_name($server, $worker_id);
	}


	/**
	 * @param $socket
	 * @param $worker_id
	 * @return string
	 * @throws ConfigException
	 * @throws Exception
	 */
	private function set_process_name($socket, $worker_id): mixed
	{
		$prefix = Config::get('id', false, 'system');
		if ($worker_id >= $socket->setting['worker_num']) {
			$name = $prefix . ' Task: No.' . $worker_id;
		} else {
			$name = $prefix . ' worker: No.' . $worker_id;
		}
		if (Snowflake::getPlatform()->isMac()) {
			return 1;
		}
		return swoole_set_process_name($name);
	}


}
