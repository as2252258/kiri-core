<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use HttpServer\Route\Annotation\Websocket as AWebsocket;
use HttpServer\Service\Http;
use HttpServer\Service\Websocket;
use Snowflake\Abstracts\Config;
use Snowflake\Error\Logger;
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
	 * @return mixed|void
	 * @throws Exception
	 */
	public function onHandler(Server $server, int $worker_id)
	{
		$get_name = $this->get_process_name($server, $worker_id);
		if (!empty($get_name) && !Snowflake::isMac()) {
			swoole_set_process_name($get_name);
		}
		if ($worker_id >= $server->setting['worker_num']) {
			return;
		}
		go(function () use ($server) {
			while ($ret = System::waitPid($server->master_pid)) {
				var_dump($ret);
			}
		});
		Snowflake::setWorkerId($server->worker_pid);
		$this->setWorkerAction($worker_id);
	}

	/**
	 * @param $worker_id
	 * @throws Exception
	 */
	private function setWorkerAction($worker_id)
	{
		try {
			$this->debug(sprintf('Worker #%d is start.....', $worker_id));
			$event = Snowflake::app()->event;
			$event->trigger(Event::SERVER_WORKER_START, [$worker_id]);
		} catch (\Throwable $exception) {
			write($exception->getMessage(), 'worker');
		}
	}

	/**
	 * @param $socket
	 * @param $worker_id
	 * @return string
	 * @throws ConfigException
	 */
	private function get_process_name($socket, $worker_id)
	{
		$prefix = rtrim(Config::get('id', 'system:'), ':');
		if ($worker_id >= $socket->setting['worker_num']) {
			return $prefix . ': Task: No.' . $worker_id;
		} else {
			return $prefix . ': worker: No.' . $worker_id;
		}
	}


}
