<?php


namespace HttpServer\Events\Trigger;


use Exception;
use HttpServer\Events\Callback;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnWorkerStart
 * @package HttpServer\Events\Trigger
 */
class OnWorkerStart extends Callback
{


	/**
	 * @param Server $server
	 * @param int $worker_id
	 *
	 * @return mixed|void
	 * @throws Exception
	 */
	public function onHandler(Server $server, $worker_id)
	{
		Logger::$worker_id = $worker_id;

		Snowflake::setProcessId($server->worker_pid);

		$get_name = $this->get_process_name($server, $worker_id);
		if (!empty($get_name) && !Snowflake::isMac()) {
			swoole_set_process_name($get_name);
		}
		$this->setWorkerAction($server, $worker_id);
	}

	/**
	 * @param $worker_id
	 * @param  $socket
	 * @throws Exception
	 */
	private function setWorkerAction($socket, $worker_id)
	{
		try {
			$event = Snowflake::get()->event;
			if ($event->exists(Event::SERVER_WORKER_START)) {
				$event->trigger(Event::SERVER_WORKER_START);
			}
		} catch (\Throwable $exception) {
			Logger::write($exception->getMessage(), 'worker');
		}
	}

	/**
	 * @param $socket
	 * @param $worker_id
	 * @return string
	 */
	private function get_process_name($socket, $worker_id)
	{
		$prefix = 'system:';
		if ($worker_id >= $socket->setting['worker_num']) {
			return $prefix . ': Task: No.' . $worker_id;
		} else {
			return $prefix . ': worker: No.' . $worker_id;
		}
	}


}
