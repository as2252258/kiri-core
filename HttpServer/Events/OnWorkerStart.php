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
		putenv('worker=' . $worker_id);
		putenv('state=start');

		$start = microtime(true);
		annotation()->read(APP_PATH . 'app', 'App');
		$this->debug(sprintf('scan app dir use time %s', microtime(true) - $start));

		$config = sweep(APP_PATH . '/config');
		foreach ($config as $key => $value) {
			Config::set($key, $value);
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
	 * @param $prefix
	 * @throws ComponentException
	 * @throws ConfigException
	 */
	public function onTask(Server $server, int $worker_id)
	{
		putenv('environmental=' . Snowflake::TASK);

		$prefix = sprintf('%s #%d Pid:%d start.', ucfirst(env('environmental')), $worker_id, $server->worker_pid);

		$start = microtime(true);
		fire(Event::SERVER_TASK_START);
		$this->debug(sprintf('%s use time %s', $prefix, microtime(true) - $start));

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

		$prefix = sprintf('%s #%d Pid:%d start.', ucfirst(env('environmental')), $worker_id, $server->worker_pid);

		try {
			$start = microtime(true);
			fire(Event::SERVER_WORKER_START, [$worker_id]);
			$this->debug(sprintf('%s use time %s', $prefix, microtime(true) - $start));
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
	 */
	private function set_process_name($socket, $worker_id): mixed
	{
		$prefix = Config::get('id', false, 'system');
		if ($worker_id >= $socket->setting['worker_num']) {
			$name = $prefix . ' Task: No.' . $worker_id;
		} else {
			$name = $prefix . ' worker: No.' . $worker_id;
		}
		if (Snowflake::isMac()) {
			return 1;
		}
		return swoole_set_process_name($name);
	}


}
