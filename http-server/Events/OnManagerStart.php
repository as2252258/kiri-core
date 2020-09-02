<?php


namespace HttpServer\Events;


use HttpServer\Events\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
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
		Snowflake::setProcessId($server->manager_pid);

		$events = Snowflake::get()->event;
		if ($events->exists(Event::SERVER_MANAGER_START)) {
			$events->trigger(Event::SERVER_MANAGER_START, null, $server);
		}
		if (Snowflake::isLinux()) {
			name('Server Manager.');
		}
	}

}
