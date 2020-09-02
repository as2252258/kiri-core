<?php


namespace HttpServer\Events\Abstracts;


use Exception;
use HttpServer\Application;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Timer;

abstract class Callback extends Application
{


	/**
	 * @param $server
	 * @param $worker_id
	 * @param $message
	 * @throws Exception
	 */
	protected function clear($server, $worker_id, $message)
	{
		Timer::clearAll();
		$event = Snowflake::get()->event;

		$event->offName(Event::EVENT_AFTER_REQUEST);
		$event->offName(Event::EVENT_BEFORE_REQUEST);
		$this->eventNotify($message, $event);

		Snowflake::clearProcessId($server->worker_pid);

		$logger = Snowflake::get()->getLogger();
		$logger->write($this->_MESSAGE[$message] . $worker_id);
		$logger->clear();
	}



	const EVENT_ERROR = 'WORKER:ERROR';
	const EVENT_STOP = 'WORKER:STOP';
	const EVENT_EXIT = 'WORKER:EXIT';


	private $_MESSAGE = [
		self::EVENT_ERROR => 'The server error. at No.',
		self::EVENT_STOP  => 'The server stop. at No.',
		self::EVENT_EXIT  => 'The server exit. at No.',
	];

	/**
	 * @param $message
	 * @param $event
	 */
	private function eventNotify($message, $event)
	{
		switch ($message) {
			case self::EVENT_ERROR:
				if (!$event->exists(Event::SERVER_WORKER_ERROR)) {
					return;
				}
				$event->trigger(Event::SERVER_WORKER_ERROR);
				break;
			case self::EVENT_EXIT:
				if (!$event->exists(Event::SERVER_WORKER_EXIT)) {
					return;
				}
				$event->trigger(Event::SERVER_WORKER_EXIT);
				break;
			case self::EVENT_STOP:
				if (!$event->exists(Event::SERVER_WORKER_STOP)) {
					return;
				}
				$event->trigger(Event::SERVER_WORKER_STOP);
				break;
		}
	}

}
