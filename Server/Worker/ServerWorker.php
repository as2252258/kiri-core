<?php

namespace Server\Worker;

use Exception;
use Server\Constant;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Runtime;
use Snowflake\Snowflake;
use Swoole\Server;


/**
 * Class ServerWorker
 * @package Server\Worker
 */
class ServerWorker extends \Server\Abstracts\Server
{


	/**
	 * @param Server $server
	 * @param int $workerId
	 * @throws Exception
	 */
	public function onWorkerStart(Server $server, int $workerId)
	{
		$this->_setConfigCache($workerId);
		$annotation = Snowflake::app()->getAnnotation();
		$annotation->read(APP_PATH . 'app');

		$loader = Snowflake::app()->getRouter();
		$loader->_loader();

		$this->runEvent(Constant::WORKER_START, null, [$server, $workerId]);
		if ($workerId >= $server->setting['worker_num'] + 1) {
			$annotation->runtime(CONTROLLER_PATH);
		}
		Event::trigger(Event::SERVER_ON_WORKER_START, [$server, $workerId]);
		name($server->worker_pid, 'Worker.' . $workerId);
	}


	/**
	 * @param $worker_id
	 * @throws Exception
	 */
	private function _setConfigCache($worker_id)
	{
		putenv('state=start');
		putenv('worker=' . $worker_id);

		$serialize = file_get_contents(storage(Runtime::CONFIG_NAME));
		if (empty($serialize)) {
			return;
		}
		Config::sets(unserialize($serialize));
	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public function onWorkerStop(Server $server, int $workerId)
	{
		$this->runEvent(Constant::WORKER_STOP, null, [$server, $workerId]);
	}


	/**
	 * @param Server $server
	 * @param int $workerId
	 */
	public function onWorkerExit(Server $server, int $workerId)
	{
		$this->runEvent(Constant::WORKER_EXIT, null, [$server, $workerId]);
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
		$this->runEvent(Constant::WORKER_ERROR, null, [$server, $worker_id, $worker_pid, $exit_code, $signal]);
	}

}
