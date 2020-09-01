<?php


namespace HttpServer\Events\Trigger;


use Exception;
use HttpServer\Events\Callback;
use Snowflake\Core\JSON;
use Snowflake\Error\Logger;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;
use Closure;

class OnShutdown extends Callback
{

	/**
	 * @param Server $server
	 */
	public function onHandler(Server $server)
	{
		var_dump($server);
	}

}
