<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
use Snowflake\Snowflake;
use Swoole\Server;

/**
 * Class OnManagerStop
 * @package HttpServer\Events
 */
class OnManagerStop extends Callback
{

	/**
	 * @param Server $server
	 * @throws Exception
	 */
	public function onHandler(Server $server)
	{
		$this->warning('manager stop.');

		fire(Event::SERVER_MANAGER_STOP, [$server]);
	}

}
