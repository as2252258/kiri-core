<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnConnect
 * @package HttpServer\Events
 */
class OnConnect extends Callback
{



	/**
	 * @param Server $server
	 * @param int $fd
	 * @param int $reactorId
	 * @throws Exception
	 */
	public function onHandler(\Swoole\Server $server, int $fd, int $reactorId)
	{
		$event = Snowflake::app()->getEvent();
		$event->trigger(Event::RECEIVE_CONNECTION, [$server, $fd, $reactorId]);

		fire(Event::SYSTEM_RESOURCE_RELEASES);
		logger()->insert();
	}


}
