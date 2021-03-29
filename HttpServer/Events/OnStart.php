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
		if (Snowflake::getPlatform()->isLinux()) {
			name($server->master_pid, 'master');
		}
		fire(Event::SERVER_EVENT_START, [$server]);
	}

}
