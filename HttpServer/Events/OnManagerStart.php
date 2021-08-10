<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Kiri\Abstracts\Config;
use Kiri\Event;
use Kiri\Kiri;
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
		Kiri::setWorkerId($server->manager_pid);

		fire(Event::SERVER_MANAGER_START, [$server]);

		name($server->manager_pid, 'manager');
	}


}
