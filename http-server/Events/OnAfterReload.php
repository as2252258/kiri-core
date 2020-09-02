<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Events\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Exception\ComponentException;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnAfterReload
 * @package HttpServer\Events
 */
class OnAfterReload extends Callback
{


	/**
	 * @param Server $server
	 * @throws ComponentException
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
		$event = Snowflake::get()->getEvent();
		if (!$event->exists(Event::SERVER_AFTER_RELOAD)) {
			return;
		}
		$event->trigger(Event::SERVER_AFTER_RELOAD, [$server]);
	}

}
