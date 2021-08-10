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
		if (Kiri::getPlatform()->isLinux()) {
			name($server->master_pid, 'master');
		}
		fire(Event::SERVER_EVENT_START, [$server]);
	}

}
