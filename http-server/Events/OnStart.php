<?php


namespace HttpServer\Events;


use HttpServer\Events\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

class OnStart extends Callback
{

	/**
	 * @param Server $server
	 * @throws \Exception
	 */
	public function onHandler($server)
	{
		$time = storage('socket.sock');
		Snowflake::writeFile($time, $server->master_pid);

		$event = Snowflake::app()->event;
		if ($event->exists(Event::SERVER_EVENT_START)) {
			$event->trigger(Event::SERVER_EVENT_START, null, $server);
		}
	}

}
