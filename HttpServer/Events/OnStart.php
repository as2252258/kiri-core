<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Abstracts\Config;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnStart
 * @package HttpServer\Events
 */
class OnStart extends Callback
{

	/**
	 * @param Server $server
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
		Snowflake::setProcessId($server->master_pid);
		if (Snowflake::getPlatform()->isLinux()) {
			name(Config::get('id', false, 'system:') . ' master.');
		}
		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::SERVER_EVENT_START, null, $server);
	}

}
