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
use Swoole\Process;
use Swoole\Server;

/**
 * Class OnWorkerStart
 * @package HttpServer\Events
 */
#[Target]
class OnWorkerStart extends Callback
{

	/** @var int 重启信号 */
	private int $signal = SIGUSR1 | SIGUSR2 | SIGTERM;


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
		putenv('worker=' . $worker_id);

		if ($worker_id >= $server->setting['worker_num']) {
			$this->onTask($server, $worker_id);
		} else {
			$this->onWorker($server, $worker_id);
		}
		$this->debug(sprintf('%s#%d start.', ucfirst(env('environmental')), $worker_id));
		Coroutine\go([$this, 'onSignal'], $server, $worker_id);
	}


	/**
	 * @param Server $server
	 * @param int $worker_id
	 * @throws ComponentException|ConfigException
	 * OnTask Worker
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
	 * @throws Exception
	 * onWorker
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
	 * @param $server
	 * @param $worker_id
	 * @return mixed
	 */
	public function onSignal(Server $server, $worker_id): void
	{
		$ret = Coroutine::waitSignal($this->signal, -1);
		Coroutine\go(function ($ret, $server, $worker_id) {
			if ($ret === true) {
				$this->ticker();
			}
			return $server->stop($worker_id);
		}, $ret, $server, $worker_id);
	}


	/**
	 * @return void
	 * @throws ComponentException
	 */
	private function ticker(): void
	{
		if (!Snowflake::app()->isRun()) {
			return;
		}

		var_dump(__METHOD__);

		sleep(1);

		$this->ticker();
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
		return swoole_set_process_name($name);
	}


}
