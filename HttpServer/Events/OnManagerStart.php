<?php


namespace HttpServer\Events;


use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Coroutine\System;
use Swoole\Process;
use Swoole\Server;

class OnManagerStart extends Callback
{

	/**
	 * @param Server $server
	 * @throws \Exception
	 */
	public function onHandler(Server $server)
	{
		$this->debug('manager start.');
		Snowflake::setWorkerId($server->manager_pid);

		$events = Snowflake::app()->event;
		$events->trigger(Event::SERVER_MANAGER_START, null, $server);
		if (Snowflake::isLinux()) {
			$prefix = Config::get('id', false, 'system:');
			name($prefix . ': Server Manager.');
		}
	}

}
