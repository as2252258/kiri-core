<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;
use Swoole\Timer;

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
		Event::trigger(Event::SERVER_BEFORE_RELOAD, [$server]);

        Snowflake::clearWorkerPid();
        Snowflake::clearTaskPid();
	}

}
