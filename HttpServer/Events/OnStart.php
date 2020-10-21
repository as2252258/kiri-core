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

		pcntl_signal(9 | 15, function () use ($server) {
			$status = 0;
			while (pcntl_waitpid($server->master_pid,$status)) {
				var_dump('skill');
				var_dump(error_get_last());
				break;
			}
		});

//		Process::signal(9, function () use ($server) {
//			while ($ret = Process::wait()) {
//				if ($ret['signal'] == 9 || $ret['signal'] == 15) {
//					$server->shutdown();
//				} else {
//					$server->reload();
//				}
//			}
//		});

	}

}
