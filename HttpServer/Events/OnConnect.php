<?php
declare(strict_types=1);

namespace HttpServer\Events;


use Exception;
use HttpServer\Abstracts\Callback;
use Snowflake\Event;
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
	public function onHandler(Server $server, int $fd, int $reactorId)
	{
		try {
			defer(fn() => fire(Event::SYSTEM_RESOURCE_RELEASES));
			if (($clientInfo = $server->getClientInfo($fd, $reactorId)) === false) {
				return;
			}
			if (isset($clientInfo['websocket_status'])) {
				return;
			}
			fire($this->getName($clientInfo, Event::SERVER_CONNECT), [$server, $fd, $reactorId]);
		} catch (\Throwable $throwable) {
			$this->addError($throwable, 'connect');
		}
	}


}
