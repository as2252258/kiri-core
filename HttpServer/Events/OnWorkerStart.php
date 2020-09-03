<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use HttpServer\Service\Http;
use HttpServer\Service\Websocket;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Snowflake;
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
	 * @return mixed|void
	 * @throws Exception
	 */
	public function onHandler(Server $server, $worker_id)
	{
		Snowflake::setProcessId($server->worker_pid);

		$get_name = $this->get_process_name($server, $worker_id);
		if (!empty($get_name) && !Snowflake::isMac()) {
			swoole_set_process_name($get_name);
		}
		if ($worker_id >= $server->setting['worker_num']) {
			return;
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
			$this->debug(sprintf('Worker #%d is start.....', $worker_id));
			$event = Snowflake::app()->event;
			if (!$event->exists(Event::SERVER_WORKER_START)) {
				return;
			}
			$event->trigger(Event::SERVER_WORKER_START);
		} catch (\Throwable $exception) {
			Snowflake::app()->getLogger()->write($exception->getMessage(), 'worker');
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
