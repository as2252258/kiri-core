<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
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
		$get_name = $this->get_process_name($server, $worker_id);
		if (!empty($get_name) && !Snowflake::isMac()) {
			swoole_set_process_name($get_name);
		}

		putenv('workerId=' . ($worker_id >= $server->setting['worker_num'] ? 'Task' : 'Worker') . '.' . $worker_id);
		if ($worker_id >= $server->setting['worker_num']) {
			fire(Event::SERVER_TASK_START);

			$this->onSign();
		} else {
			Snowflake::setWorkerId($server->worker_pid);
			$this->setWorkerAction($worker_id);
		}
	}

	public function onSign()
	{
		Coroutine::getContext()['isComplete'] = false;
		go(function () {
			$sigkill = Coroutine::waitSignal(SIGTERM | SIGKILL | SIGUSR2, -1);
			var_dump($sigkill);
			if ($sigkill === false) {
				return;
			}
			while (true) {
				if (!isset(Coroutine::getContext(Coroutine::getPcid())['isComplete'])) {
					break;
				}
				$content = Coroutine::getContext(Coroutine::getPcid())['isComplete'];
				if ($content === true) {
					break;
				}
			}
		});
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
