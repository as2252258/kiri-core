<?php


namespace HttpServer\Events;


use Exception;
use HttpServer\Events\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnBeforeReload
 * @package HttpServer\Events
 */
class OnBeforeReload extends Callback
{

	/**
	 * @param Server $server
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
		$event = Snowflake::app()->getEvent();
		if (!$event->exists(Event::SERVER_BEFORE_RELOAD)) {
			return;
		}
		$event->trigger(Event::SERVER_BEFORE_RELOAD, [$server]);
	}

}
