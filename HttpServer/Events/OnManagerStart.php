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
 * Class OnManagerStart
 * @package HttpServer\Events
 */
class OnManagerStart extends Callback
{


	/**
	 * @param Server $server
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
		Snowflake::setWorkerId($server->manager_pid);

		fire(Event::SERVER_MANAGER_START, [$server]);

		name($server->manager_pid, 'manager');
	}


}
