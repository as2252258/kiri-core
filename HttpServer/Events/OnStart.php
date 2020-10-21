<?php


namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Coroutine\System;
use Swoole\Process;
use Swoole\Server;

class OnStart extends Callback
{

	/**
	 * @param Server $server
	 * @throws \Exception
	 */
	public function onHandler(Server $server)
	{
		Snowflake::setProcessId($server->master_pid);

		$event = Snowflake::app()->event;
		$event->trigger(Event::SERVER_EVENT_START, null, $server);

		if (Snowflake::isLinux()) {
			name(rtrim(Config::get('id', 'system:'), ':'));
		}
		while ($ret = Process::wait()) {
			var_dump($ret);
		}
	}

}
