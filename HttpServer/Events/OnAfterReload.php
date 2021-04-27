<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
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
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
		Event::trigger(Event::SERVER_AFTER_RELOAD, [$server]);
	}

}
